<?php

namespace App\Support\Activity;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(Model $subject, string $action, ?int $organizationId = null, array $metadata = []): ActivityLog
    {
        $organizationId ??= static::resolveOrganizationId($subject);

        return ActivityLog::create([
            'organization_id' => $organizationId,
            'actor_user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }

    protected static function resolveOrganizationId(Model $subject): ?int
    {
        if ($subject->getAttribute('organization_id')) {
            return (int) $subject->getAttribute('organization_id');
        }

        if ($subject->getAttribute('invoice_id') && method_exists($subject, 'invoice')) {
            return optional($subject->invoice)->organization_id;
        }

        return null;
    }
}
