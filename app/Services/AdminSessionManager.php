<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSessionManager
{
    public function supportsDatabaseSessions(): bool
    {
        $table = config('session.table');

        return config('session.driver') === 'database'
            && is_string($table)
            && Schema::connection(config('session.connection'))->hasTable($table);
    }

    public function activeSessions(User $user, string $currentSessionId): LengthAwarePaginator
    {
        $query = $this->sessionTable()
            ->where('user_id', $user->getKey())
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity');

        return $query->paginate(10, ['id', 'ip_address', 'user_agent', 'last_activity'], 'sessions_page')
            ->through(fn (object $session): object => (object) [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current' => hash_equals($currentSessionId, (string) $session->id),
            ]);
    }

    public function revoke(User $user, string $sessionId, string $currentSessionId): bool
    {
        if (! $this->supportsDatabaseSessions() || hash_equals($currentSessionId, $sessionId)) {
            return false;
        }

        return $this->sessionTable()
            ->where('id', $sessionId)
            ->where('user_id', $user->getKey())
            ->delete() === 1;
    }

    public function revokeAll(User $user): void
    {
        if (! $this->supportsDatabaseSessions()) {
            return;
        }

        $this->sessionTable()->where('user_id', $user->getKey())->delete();
    }

    private function sessionTable(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table((string) config('session.table'));
    }
}
