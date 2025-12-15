<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $user = $request->user();
        $org = $user->currentOrganization;

        $this->authorize('manageMembers', $org);

        $logs = ActivityLog::with('actor')
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->string('subject_type')))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('audit.index', compact('logs'));
    }
}
