<?php

namespace App\Support;

use App\Models\User;

class Permissions
{
    public static function roleFor(User $user, int $organizationId): ?string
    {
        $org = $user->organizations()->where('organization_id', $organizationId)->first();

        return $org?->pivot?->role;
    }

    public static function allows(?string $role, string $resource, string $ability): bool
    {
        if (! $role) {
            return false;
        }

        if ($role === 'owner') {
            return true;
        }

        $map = static::map();

        $abilities = $map[$role][$resource] ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    protected static function map(): array
    {
        return [
            'admin' => [
                'clients' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'projects' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'invoices' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'payments' => ['viewAny', 'view', 'create', 'delete'],
            ],
            'member' => [
                'clients' => ['viewAny', 'view'],
                'projects' => ['viewAny', 'view'],
                'invoices' => ['viewAny', 'view', 'create', 'update'],
                'payments' => ['viewAny', 'view'],
            ],
        ];
    }
}
