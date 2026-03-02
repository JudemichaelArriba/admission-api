<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'action' => 'nullable|string|max:80',
            'target_type' => 'nullable|string|max:60',
            'target_id' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = AuditLog::with('actor')->latest('id');
        if (isset($validated['action'])) {
            $query->where('action', $validated['action']);
        }
        if (isset($validated['target_type'])) {
            $query->where('target_type', $validated['target_type']);
        }
        if (isset($validated['target_id'])) {
            $query->where('target_id', $validated['target_id']);
        }

        return response()->json($query->paginate($perPage));
    }
}
