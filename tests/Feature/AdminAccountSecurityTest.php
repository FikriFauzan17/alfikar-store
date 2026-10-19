<?php

use App\Models\User;
use App\Services\AdminTwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OTPHP\InternalClock;
use OTPHP\TOTP;

uses(RefreshDatabase::class);

it('requires password verification before enabling TOTP and only shows recovery codes once', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);

    $securityPage = $this->actingAs($admin)->get(route('admin.security.index'));

    $securityPage->assertOk()->assertSee('Mulai Pengaturan 2FA');
    expect($securityPage->headers->get('Cache-Control'))->toContain('no-store');

    $this->post(route('admin.security.two-factor.setup'), [
        'current_password' => 'incorrect-password',
    ])->assertSessionHasErrors('current_password')
        ->assertSessionMissing('_old_input.current_password');

    expect(session()->has('admin.two_factor.setup_secret.'.$admin->id))->toBeFalse();

    $this->post(route('admin.security.two-factor.setup'), [
        'current_password' => 'password',
    ])->assertRedirect(route('admin.security.index'));

    $encryptedSecret = session('admin.two_factor.setup_secret.'.$admin->id);
    $secret = Crypt::decryptString($encryptedSecret);
    expect($encryptedSecret)->not->toBe($secret);
    $validCode = TOTP::createFromSecret($secret, new InternalClock)->now();
    $invalidCode = str_pad((string) ((intval($validCode) + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    $this->post(route('admin.security.two-factor.enable'), [
        'current_password' => 'password',
        'code' => $invalidCode,
    ])->assertSessionHasErrors('code');

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();

    $enableResponse = $this->post(route('admin.security.two-factor.enable'), [
        'current_password' => 'password',
        'code' => $validCode,
    ]);
    $shownRecoveryCodes = [];
    $enableResponse->assertOk()
        ->assertViewHas('recoveryCodes', function (array $codes) use (&$shownRecoveryCodes): bool {
            $shownRecoveryCodes = $codes;

            return count($codes) === 8;
        })
        ->assertSessionMissing('encrypted_recovery_codes');

    $enabledAdmin = $admin->fresh();
    $recoveryCodes = $shownRecoveryCodes;
    expect($enabledAdmin->hasTwoFactorEnabled())->toBeTrue()
        ->and($enabledAdmin->totp_recovery_codes)->toHaveCount(8)
        ->and(Hash::check(
            str_replace('-', '', $recoveryCodes[0]),
            $enabledAdmin->totp_recovery_codes[0],
        ))->toBeTrue();
    expect(DB::table('users')->where('id', $admin->id)->value('totp_secret'))->not->toBe($secret);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.two_factor.enabled',
        'subject_id' => $admin->id,
    ]);

    $recoveryCode = $recoveryCodes[0];
    $enableResponse->assertSee($recoveryCode);
    $this->get(route('admin.security.index'))->assertDontSee($recoveryCode);
});

it('does not authenticate an administrator until a valid TOTP code is supplied', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);
    $secret = $twoFactor->generateSecret();
    $admin->totp_secret = $secret;
    $admin->totp_recovery_codes = $twoFactor->hashRecoveryCodes(['ABCD-EF01-2345-6789']);
    $admin->totp_enabled_at = now();
    $admin->save();
    $validCode = TOTP::createFromSecret($secret, new InternalClock)->now();
    $invalidCode = str_pad((string) ((intval($validCode) + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    $this->post(route('admin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    $this->assertGuest();

    $this->post(route('admin.two-factor.verify'), ['code' => $invalidCode])
        ->assertRedirect(route('admin.two-factor.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();

    $this->post(route('admin.two-factor.verify'), ['code' => 'ZZZZ-ZZZZ-ZZZZ-ZZZZ'])
        ->assertRedirect(route('admin.two-factor.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();

    $this->post(route('admin.two-factor.verify'), ['code' => $validCode])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.login',
        'subject_id' => $admin->id,
    ]);

    $this->post(route('admin.logout'))->assertRedirect(route('login'));

    $this->post(route('admin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    $this->post(route('admin.two-factor.verify'), ['code' => $validCode])
        ->assertRedirect(route('admin.two-factor.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('consumes a recovery code once when completing admin login', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);
    $admin->totp_secret = $twoFactor->generateSecret();
    $admin->totp_recovery_codes = $twoFactor->hashRecoveryCodes(['ABCD-EF01-2345-6789']);
    $admin->totp_enabled_at = now();
    $admin->save();

    $this->post(route('admin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    $this->post(route('admin.two-factor.verify'), ['code' => 'ABCD-EF01-2345-6789'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
    expect($admin->fresh()->totp_recovery_codes)->toBe([]);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.two_factor.recovery_code_used',
        'subject_id' => $admin->id,
    ]);

    $this->post(route('admin.logout'))->assertRedirect(route('login'));
    $this->post(route('admin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));
    $this->post(route('admin.two-factor.verify'), ['code' => 'ABCD-EF01-2345-6789'])
        ->assertRedirect(route('admin.two-factor.challenge'))
        ->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('rate limits repeated two-factor verification failures', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);
    $admin->totp_secret = $twoFactor->generateSecret();
    $admin->totp_recovery_codes = $twoFactor->hashRecoveryCodes([]);
    $admin->totp_enabled_at = now();
    $admin->save();
    $validCode = TOTP::createFromSecret($admin->totp_secret, new InternalClock)->now();
    $invalidCode = str_pad((string) ((intval($validCode) + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    $this->post(route('admin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.challenge'));

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.two-factor.verify'), ['code' => $invalidCode])
            ->assertRedirect(route('admin.two-factor.challenge'));
    }

    $this->post(route('admin.two-factor.verify'), ['code' => $invalidCode])->assertTooManyRequests();
});

it('requires both password and a valid factor to disable two-factor authentication', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);
    $secret = $twoFactor->generateSecret();
    $admin->totp_secret = $secret;
    $admin->totp_recovery_codes = $twoFactor->hashRecoveryCodes(['ABCD-EF01-2345-6789']);
    $admin->totp_enabled_at = now();
    $admin->save();
    $validCode = TOTP::createFromSecret($secret, new InternalClock)->now();
    $invalidCode = str_pad((string) ((intval($validCode) + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    $this->actingAs($admin)
        ->post(route('admin.security.two-factor.disable'), [
            'current_password' => 'incorrect-password',
            'code' => $validCode,
        ])
        ->assertSessionHasErrors('current_password');

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->post(route('admin.security.two-factor.disable'), [
        'current_password' => 'password',
        'code' => $invalidCode,
    ])->assertSessionHasErrors('code');

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->post(route('admin.security.two-factor.disable'), [
        'current_password' => 'password',
        'code' => 'ABCD-EF01-2345-6789',
    ])->assertRedirect(route('admin.security.index'));

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.two_factor.disabled',
        'subject_id' => $admin->id,
    ]);
});

it('requires current password and a factor before rotating recovery codes', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $twoFactor = app(AdminTwoFactorService::class);
    $secret = $twoFactor->generateSecret();
    $admin->totp_secret = $secret;
    $admin->totp_recovery_codes = $twoFactor->hashRecoveryCodes(['ABCD-EF01-2345-6789']);
    $admin->totp_enabled_at = now();
    $admin->save();
    $validCode = TOTP::createFromSecret($secret, new InternalClock)->now();

    $response = $this->actingAs($admin)
        ->post(route('admin.security.two-factor.recovery-codes'), [
            'current_password' => 'password',
            'code' => $validCode,
        ]);
    $response->assertOk()->assertViewHas('recoveryCodes', fn (array $codes): bool => count($codes) === 8)
        ->assertSessionMissing('encrypted_recovery_codes');

    expect($admin->fresh()->totp_recovery_codes)->toHaveCount(8);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.two_factor.recovery_codes.regenerated',
        'subject_id' => $admin->id,
    ]);
});

it('rejects inactive accounts on the next request and at login', function () {
    $inactiveAdmin = User::factory()->create(['is_admin' => true, 'is_active' => false]);

    $this->actingAs($inactiveAdmin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('account');

    $this->assertGuest();

    $this->post(route('admin.login'), [
        'email' => $inactiveAdmin->email,
        'password' => 'password',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('credentials');

    $this->assertGuest();
});

it('prevents deactivating the last active administrator', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.status', $admin), ['is_active' => '0'])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('is_active');

    expect($admin->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseMissing('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.account.deactivated',
        'subject_id' => $admin->id,
    ]);
});

it('lets an authorized administrator deactivate another account and records the action', function () {
    config(['session.driver' => 'database']);

    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => true]);
    DB::table('sessions')->insert([
        'id' => 'inactive-target-session',
        'user_id' => $target->id,
        'ip_address' => '192.0.2.12',
        'user_agent' => 'Target admin device',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $target), ['is_active' => '0'])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'Akun berhasil dinonaktifkan.');

    expect($target->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseMissing('sessions', ['id' => 'inactive-target-session']);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.account.deactivated',
        'subject_id' => $target->id,
    ]);

    $this->actingAs($target->fresh())
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('lists and revokes only the authenticated users database sessions', function () {
    config(['session.driver' => 'database']);

    $admin = User::factory()->create(['is_admin' => true]);
    $otherAdmin = User::factory()->create(['is_admin' => true]);

    DB::table('sessions')->insert([
        [
            'id' => 'other-device-session',
            'user_id' => $admin->id,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Alfikar test device',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'foreign-device-session',
            'user_id' => $otherAdmin->id,
            'ip_address' => '192.0.2.11',
            'user_agent' => 'Other admin device',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.security.index'))
        ->assertOk()
        ->assertSee('Alfikar test device')
        ->assertDontSee('Other admin device');

    $this->delete(route('admin.security.sessions.destroy', 'foreign-device-session'))->assertNotFound();
    $this->assertDatabaseHas('sessions', ['id' => 'foreign-device-session', 'user_id' => $otherAdmin->id]);

    $this->delete(route('admin.security.sessions.destroy', 'other-device-session'))
        ->assertRedirect(route('admin.security.index'))
        ->assertSessionHas('status', 'Sesi berhasil dicabut.');

    $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'foreign-device-session', 'user_id' => $otherAdmin->id]);
});

it('does not expose user management or security controls to non-admins', function () {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->get(route('admin.security.index'))->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->get(route('admin.security.index'))->assertForbidden();
});

it('renders the account list with access status for administrators', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Alfikar Admin']);
    User::factory()->create(['name' => 'Katalog Editor', 'email' => 'editor@example.test']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Alfikar Admin')
        ->assertSee('editor@example.test')
        ->assertSee('Tambah Pengguna')
        ->assertSee('Keamanan akun');
});
