<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function logAudit(Request $request, string $action, string $targetType, int $targetId, array $details = []): void
    {
        AuditLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
