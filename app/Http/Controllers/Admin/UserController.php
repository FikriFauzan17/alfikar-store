<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreAdminUserRequest $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $audit, $validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->is_admin = (bool) $validated['is_admin'];
            $user->save();

            $audit->record($request->user(), 'admin.account.created', $user);
        });

        return redirect()->route('admin.dashboard')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function updateStatus(
        Request $request,
        User $user,
        AdminAuditLogger $audit,
        AdminSessionManager $sessions,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $isActive = (bool) $validated['is_active'];

        DB::transaction(function () use ($request, $audit, $isActive, $user): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($target->is_active === $isActive) {
                return;
            }

            if ($target->is_admin && $target->is_active && ! $isActive) {
                $activeAdministrators = User::query()
                    ->where('is_admin', true)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get(['id']);

                if ($activeAdministrators->count() <= 1) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Setidaknya satu administrator aktif harus dipertahankan.',
                    ]);
                }
            }

            $target->is_active = $isActive;
            $target->save();
            $audit->record(
                $request->user(),
                $isActive ? 'admin.account.activated' : 'admin.account.deactivated',
                $target,
            );
        });

        if (! $isActive) {
            $sessions->revokeAll($user);

            if ($request->user()->is($user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Akun berhasil dinonaktifkan.');
            }
        }

        return redirect()->route('admin.users.index')->with(
            'status',
            $isActive ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.',
        );
    }
}
