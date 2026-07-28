<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Audit
{
    /**
     * Record an audit-trail entry (who did what to which entity, when).
     * Safe to call from anywhere; the actor is the currently authenticated user.
     */
    public static function log(string $action, ?Model $entity = null, ?string $description = null, array $meta = []): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id'     => $user?->id,
            'actor_name'  => $user?->name ?? 'System',
            'action'      => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id'   => $entity?->getKey(),
            'description' => $description,
            'meta'        => $meta ?: null,
            'created_at'  => now(),
        ]);
    }
}
