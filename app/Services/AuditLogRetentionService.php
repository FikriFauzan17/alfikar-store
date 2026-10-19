<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditLogRetentionService
{
    public function pruneExpired(): int
    {
        return DB::table('audit_logs')
            ->where('created_at', '<', now()->subMonths(12))
            ->delete();
    }
}
