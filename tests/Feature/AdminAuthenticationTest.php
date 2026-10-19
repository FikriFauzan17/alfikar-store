<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('renders an accessible branded login form without unavailable account flows', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Masuk ke Admin Alfikar')
        ->assertSee('Kelola Koleksi,')
        ->assertSee('Hadirkan Inspirasi')
        ->assertSee('action="'.route('admin.login').'"', false)
        ->assertSee('autocomplete="username"', false)
        ->assertSee('autocomplete="current-password"', false)
        ->assertSee('aria-label="Tampilkan kata sandi"', false)
        ->assertSee('Kembali ke Website')
        ->assertDontSee('Lupa Password')
        ->assertDontSee('Daftar');
});

it('redirects guests from admin pages to the login form', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('authenticates administrators and records the login', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->get(route('login'));
    $sessionIdBeforeLogin = session()->getId();

    $this->post(route('admin.login'), [
        'email' => strtoupper($user->email),
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionIdBeforeLogin);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $user->id,
        'event' => 'admin.login',
    ]);
});

it('denies dashboard access to authenticated non-administrators', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('returns the same credential error for non-admin accounts', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('credentials');

    $this->assertGuest();
});

it('keeps login validation redirects on the internal login route', function () {
    $this->withHeader('referer', 'https://outside.example/')
        ->post(route('admin.login'), [])
        ->assertRedirect(route('login'));
});

it('invalidates the session and records admin logout', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->withSession(['admin_marker' => 'remove-me'])
        ->post(route('admin.logout'))
        ->assertRedirect(route('login'))
        ->assertSessionMissing('admin_marker');

    $this->assertGuest();
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $user->id,
        'event' => 'admin.logout',
    ]);
});

it('rate limits repeated admin login attempts', function () {
    foreach (range(1, 5) as $attemptNumber) {
        $this->post(route('admin.login'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'));
    }

    $this->post(route('admin.login'), [
        'email' => 'unknown@example.test',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('rate limits admin login attempts by IP across different accounts', function () {
    foreach (range(1, 20) as $attemptNumber) {
        $this->from(route('login'))
            ->post(route('admin.login'), [
                'email' => 'unknown-'.$attemptNumber.'@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'));
    }

    $this->post(route('admin.login'), [
        'email' => 'another@example.test',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('creates an administrator only from confirmed hidden password input', function () {
    $password = 'A-strong-admin-password-2026';

    $this->artisan('app:make-admin')
        ->expectsQuestion('Nama admin', 'Alfikar Admin')
        ->expectsQuestion('Email admin', 'ADMIN@example.test')
        ->expectsQuestion('Kata sandi (minimal 12 karakter)', $password)
        ->expectsQuestion('Ulangi kata sandi', $password)
        ->assertExitCode(0);

    $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

    $this->assertModelExists($user);
    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check($password, $user->password))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'admin.account.created',
        'subject_id' => $user->id,
    ]);
});

it('does not seed a default user or administrator', function () {
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 0);
});

it('does not expose public registration', function () {
    $this->get('/register')->assertNotFound();
});
