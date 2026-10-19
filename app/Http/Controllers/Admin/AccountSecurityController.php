<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminSessionManager;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccountSecurityController extends Controller
{
    public function index(
        Request $request,
        AdminSessionManager $sessions,
    ): Response {
        $user = $this->authenticatedUser($request);
        $encryptedSetupSecret = $request->session()->get($this->setupSessionKey($user));
        $setupSecret = is_string($encryptedSetupSecret)
            ? Crypt::decryptString($encryptedSetupSecret)
            : null;
        $activeSessions = $sessions->supportsDatabaseSessions()
            ? $sessions->activeSessions($user, $request->session()->getId())
            : null;

        return response()->view('admin.security.index', [
            'activeSessions' => $activeSessions,
            'setupSecret' => $setupSecret,
            'user' => $user,
        ])->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Pragma', 'no-cache');
    }

    public function startSetup(Request $request, AdminTwoFactorService $twoFactor): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak valid.']);
        }

        if ($user->hasTwoFactorEnabled()) {
            return back()->withErrors(['two_factor' => 'Verifikasi dua langkah sudah aktif.']);
        }

        $request->session()->put(
            $this->setupSessionKey($user),
            Crypt::encryptString($twoFactor->generateSecret()),
        );

        return redirect()->route('admin.security.index')
            ->with('status', 'Pindai atau masukkan kunci TOTP pada aplikasi autentikator, lalu verifikasi kode.');
    }

    public function enable(
        Request $request,
        AdminTwoFactorService $twoFactor,
        AdminAuditLogger $audit,
    ): RedirectResponse|Response {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);
        $encryptedSecret = $request->session()->get($this->setupSessionKey($user));
        $secret = is_string($encryptedSecret) ? Crypt::decryptString($encryptedSecret) : null;

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak valid.']);
        }

        if (! is_string($secret)) {
            return back()->withErrors(['two_factor' => 'Mulai pengaturan verifikasi dua langkah terlebih dahulu.']);
        }

        if (! $twoFactor->verifyCode($secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Kode autentikasi tidak valid atau sudah kedaluwarsa.']);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();
        $recoveryCodeHashes = $twoFactor->hashRecoveryCodes($recoveryCodes);

        DB::transaction(function () use ($audit, $request, $recoveryCodeHashes, $secret, $twoFactor, $user, $validated): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($account->hasTwoFactorEnabled()) {
                throw ValidationException::withMessages([
                    'two_factor' => 'Verifikasi dua langkah sudah aktif.',
                ]);
            }

            $account->totp_secret = $secret;
            $account->totp_recovery_codes = $recoveryCodeHashes;
            $account->totp_enabled_at = now();
            $account->totp_last_code_fingerprint = $twoFactor->fingerprint($validated['code']);
            $account->totp_last_code_used_at = now();
            $account->save();

            $audit->record($request->user(), 'admin.two_factor.enabled', $account);
        });

        $request->session()->forget($this->setupSessionKey($user));
        $request->session()->regenerate();

        return $this->recoveryCodesResponse(
            $user,
            $recoveryCodes,
            'Verifikasi dua langkah aktif. Simpan recovery code berikut di tempat aman.',
        );
    }

    public function cancelSetup(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $request->session()->forget($this->setupSessionKey($user));

        return redirect()->route('admin.security.index')->with('status', 'Pengaturan verifikasi dua langkah dibatalkan.');
    }

    public function disable(
        Request $request,
        AdminTwoFactorService $twoFactor,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak valid.']);
        }

        $factor = $twoFactor->consumeFactor($user->getKey(), trim($validated['code']));

        if ($factor === null) {
            return back()->withErrors(['code' => 'Kode autentikasi atau recovery code tidak valid.']);
        }

        DB::transaction(function () use ($audit, $factor, $request, $user): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $account->hasTwoFactorEnabled()) {
                throw ValidationException::withMessages([
                    'two_factor' => 'Verifikasi dua langkah sudah tidak aktif.',
                ]);
            }

            $account->totp_secret = null;
            $account->totp_recovery_codes = null;
            $account->totp_enabled_at = null;
            $account->totp_last_code_fingerprint = null;
            $account->totp_last_code_used_at = null;
            $account->save();

            $audit->record($request->user(), 'admin.two_factor.disabled', $account);

            if ($factor === 'recovery') {
                $audit->record($request->user(), 'admin.two_factor.recovery_code_used', $account);
            }
        });

        $request->session()->regenerate();

        return redirect()->route('admin.security.index')->with('status', 'Verifikasi dua langkah dinonaktifkan.');
    }

    public function regenerateRecoveryCodes(
        Request $request,
        AdminTwoFactorService $twoFactor,
        AdminAuditLogger $audit,
    ): RedirectResponse|Response {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak valid.']);
        }

        $factor = $twoFactor->consumeFactor($user->getKey(), trim($validated['code']));

        if ($factor === null) {
            return back()->withErrors(['code' => 'Kode autentikasi atau recovery code tidak valid.']);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();
        $recoveryCodeHashes = $twoFactor->hashRecoveryCodes($recoveryCodes);

        DB::transaction(function () use ($audit, $factor, $recoveryCodeHashes, $request, $user): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $account->hasTwoFactorEnabled()) {
                throw ValidationException::withMessages([
                    'two_factor' => 'Verifikasi dua langkah sudah tidak aktif.',
                ]);
            }

            $account->totp_recovery_codes = $recoveryCodeHashes;
            $account->save();

            $audit->record($request->user(), 'admin.two_factor.recovery_codes.regenerated', $account);

            if ($factor === 'recovery') {
                $audit->record($request->user(), 'admin.two_factor.recovery_code_used', $account);
            }
        });

        $request->session()->regenerate();

        return $this->recoveryCodesResponse(
            $user,
            $recoveryCodes,
            'Recovery code baru dibuat. Kode lama tidak lagi berlaku.',
        );
    }

    public function destroySession(
        Request $request,
        string $sessionId,
        AdminAuditLogger $audit,
        AdminSessionManager $sessions,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        $currentSessionId = $request->session()->getId();

        if (! $sessions->revoke($user, $sessionId, $currentSessionId)) {
            throw new NotFoundHttpException;
        }

        $audit->record($user, 'admin.session.revoked', $user);

        return redirect()->route('admin.security.index')->with('status', 'Sesi berhasil dicabut.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function setupSessionKey(User $user): string
    {
        return 'admin.two_factor.setup_secret.'.$user->getKey();
    }

    /**
     * @param  list<string>  $recoveryCodes
     */
    private function recoveryCodesResponse(User $user, array $recoveryCodes, string $message): Response
    {
        return response()->view('admin.security.recovery-codes', [
            'message' => $message,
            'recoveryCodes' => $recoveryCodes,
            'user' => $user,
        ])->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Pragma', 'no-cache');
    }
}
