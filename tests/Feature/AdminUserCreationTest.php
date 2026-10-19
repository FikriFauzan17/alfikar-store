<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shows the admin-only add user form with the existing account fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('Tambah Pengguna')
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee('name="is_admin"', false)
        ->assertSee('Simpan')
        ->assertSee('Batal');
});

it('does not let guests or non-admin users create accounts', function () {
    $nonAdmin = User::factory()->create();

    $this->get(route('admin.users.create'))
        ->assertRedirect(route('login'));

    $this->post(route('admin.users.store'), [
        'name' => 'New Account',
        'email' => 'new-account@example.test',
        'password' => 'A-strong-password-2026',
        'password_confirmation' => 'A-strong-password-2026',
        'is_admin' => '1',
    ])->assertRedirect(route('login'));

    $this->actingAs($nonAdmin)
        ->get(route('admin.users.create'))
        ->assertForbidden();

    $this->actingAs($nonAdmin)
        ->post(route('admin.users.store'), [
            'name' => 'New Account',
            'email' => 'new-account@example.test',
            'password' => 'A-strong-password-2026',
            'password_confirmation' => 'A-strong-password-2026',
            'is_admin' => '1',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'new-account@example.test']);
});

it('creates a user with the selected admin role and records the action', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $password = 'A-strong-password-2026';

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Admin',
            'email' => 'NEW-ADMIN@example.test',
            'password' => $password,
            'password_confirmation' => $password,
            'is_admin' => '1',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('status', 'Pengguna berhasil ditambahkan.');

    $user = User::query()->where('email', 'new-admin@example.test')->firstOrFail();

    $this->assertModelExists($user);
    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check($password, $user->password))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'admin.account.created',
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);
});

it('rejects invalid account data without flashing secret inputs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $password = 'short';

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            'name' => 'Keep This Name',
            'email' => 'invalid-email',
            'password' => $password,
            'password_confirmation' => 'different',
            'is_admin' => 'invalid',
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors(['email', 'password', 'is_admin'])
        ->assertSessionHas('_old_input.name', 'Keep This Name')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->assertDatabaseCount('users', 1);
});
