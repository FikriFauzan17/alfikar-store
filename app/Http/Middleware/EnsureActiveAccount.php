<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function __construct(private AdminSessionManager $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_active) {
            return $next($request);
        }

        $this->sessions->revokeAll($user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['account' => 'Akun ini tidak aktif. Hubungi administrator.']);
    }
}
