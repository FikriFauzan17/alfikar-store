<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogger
{
    public function record(Authenticatable $actor, string $event, Model $subject): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->getAuthIdentifier(),
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
        ]);
    }
}
