<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditApiController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResource
    {
        $org = $request->user()->currentOrganization;
        $this->authorize('manageMembers', $org);

        $logs = ActivityLog::with('actor')
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->string('subject_type')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->orderByDesc('created_at')
            ->paginate(15);

        return JsonResource::collection($logs);
    }
}
