<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\InternalClock;
use OTPHP\TOTP;

class AdminTwoFactorService
{
    public function generateSecret(): string
    {
        return TOTP::generate(new InternalClock)->getSecret();
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return preg_match('/\A\d{6}\z/', $code) === 1
            && TOTP::createFromSecret($secret, new InternalClock)->verify($code, null, 1);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            static fn (int $_): string => implode('-', str_split(Str::upper(bin2hex(random_bytes(8))), 4)),
            range(1, 8),
        );
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(
            fn (string $code): string => Hash::make($this->normalizeRecoveryCode($code)),
            $codes,
        );
    }

    public function fingerprint(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    public function consumeFactor(int|string $userId, string $code): ?string
    {
        return DB::transaction(function () use ($userId, $code): ?string {
            $user = User::query()->lockForUpdate()->find($userId);

            if (
                ! $user instanceof User
                || ! $user->is_admin
                || ! $user->is_active
                || ! $user->hasTwoFactorEnabled()
                || ! is_string($user->totp_secret)
            ) {
                return null;
            }

            if ($this->verifyCode($user->totp_secret, $code)) {
                $fingerprint = $this->fingerprint($code);
                $recentlyUsedAt = now()->subSeconds(30);

                if (
                    $user->totp_last_code_used_at?->greaterThan($recentlyUsedAt)
                    && is_string($user->totp_last_code_fingerprint)
                    && hash_equals($user->totp_last_code_fingerprint, $fingerprint)
                ) {
                    return null;
                }

                $user->totp_last_code_fingerprint = $fingerprint;
                $user->totp_last_code_used_at = now();
                $user->save();

                return 'totp';
            }

            $normalizedCode = $this->normalizeRecoveryCode($code);
            $recoveryCodes = $user->totp_recovery_codes ?? [];

            foreach ($recoveryCodes as $index => $recoveryCodeHash) {
                if (Hash::check($normalizedCode, $recoveryCodeHash)) {
                    unset($recoveryCodes[$index]);
                    $user->totp_recovery_codes = array_values($recoveryCodes);
                    $user->save();

                    return 'recovery';
                }
            }

            return null;
        });
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return Str::upper(str_replace(['-', ' '], '', trim($code)));
    }
}
