<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->latest('created_at');

        if ($request->filled('actor_id')) {
            $query->where('actor_id', (int) $request->input('actor_id'));
        }

        if ($request->filled('entity')) {
            $query->where('auditable_type', $request->input('entity'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(50)->withQueryString();
        $actors = User::where('role', 'admin')->orderBy('name')->get(['id', 'name']);
        $entities = AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type');

        return view('admin.system.audit-log.index', compact('logs', 'actors', 'entities'));
    }
}
