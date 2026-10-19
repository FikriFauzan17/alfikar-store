<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, AdminAuditLogger $audit): RedirectResponse
    {
        if (Auth::check()) {
            $existingUser = $request->user();

            if ($existingUser instanceof User && $existingUser->is_admin && $existingUser->is_active) {
                return redirect()->route('admin.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $credentials = $request->validated();

        if (! Auth::validate([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_admin' => true,
            'is_active' => true,
        ])) {
            return redirect()->route('login')
                ->withErrors(['credentials' => 'Email atau kata sandi tidak valid.'])
                ->withInput(['email' => $credentials['email']]);
        }

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('is_admin', true)
            ->where('is_active', true)
            ->firstOrFail();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put('admin.two_factor.pending_user_id', $user->getKey());

            return redirect()->route('admin.two-factor.challenge');
        }

        return $this->authenticate($request, $user, $audit);
    }

    public function challenge(Request $request): Response|RedirectResponse
    {
        $pendingUserId = $request->session()->get('admin.two_factor.pending_user_id');
        $user = is_numeric($pendingUserId)
            ? User::query()->whereKey($pendingUserId)->where('is_admin', true)->where('is_active', true)->first()
            : null;

        if (! $user instanceof User || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('admin.two_factor.pending_user_id');

            return redirect()->route('login');
        }

        return response()->view('admin.auth.two-factor-challenge')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Pragma', 'no-cache');
    }

    public function verifyTwoFactor(
        Request $request,
        AdminTwoFactorService $twoFactor,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);
        $pendingUserId = $request->session()->get('admin.two_factor.pending_user_id');
        $user = is_numeric($pendingUserId)
            ? User::query()->whereKey($pendingUserId)->where('is_admin', true)->where('is_active', true)->first()
            : null;

        if (! $user instanceof User || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('admin.two_factor.pending_user_id');

            return redirect()->route('login');
        }

        $factor = $twoFactor->consumeFactor($user->getKey(), trim($validated['code']));

        if ($factor === null) {
            return redirect()->route('admin.two-factor.challenge')
                ->withErrors(['code' => 'Kode autentikasi atau recovery code tidak valid.']);
        }

        $request->session()->forget('admin.two_factor.pending_user_id');

        if ($factor === 'recovery') {
            $audit->record($user, 'admin.two_factor.recovery_code_used', $user);
        }

        return $this->authenticate($request, $user, $audit);
    }

    private function authenticate(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        $audit->record($user, 'admin.login', $user);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user instanceof User) {
            $user->auditLogs()->create([
                'event' => 'admin.logout',
                'subject_type' => User::class,
                'subject_id' => $user->getKey(),
            ]);
        }

        return redirect()->route('login');
    }
}
