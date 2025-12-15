<?php

namespace App\Support;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class Permissions
{
    public static function roleFor(User $user, int $organizationId): ?string
    {
        $role = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $user->id)
            ->where('role_user.organization_id', $organizationId)
            ->select('roles.name')
            ->first();

        if ($role?->name) {
            return $role->name;
        }

        $orgPivot = $user->organizations()->where('organization_id', $organizationId)->first();

        if (! $orgPivot?->pivot?->role) {
            return null;
        }

        // Fallback to legacy pivot, ensure default roles exist and sync.
        static::ensureDefaultRolesForOrganization($organizationId);
        static::syncUserRole($user, $organizationId, $orgPivot->pivot->role);

        return $orgPivot->pivot->role;
    }

    public static function allows(?string $role, string $resource, string $ability): bool
    {
        if (! $role) {
            return false;
        }

        if ($role === 'owner') {
            return true;
        }

        $permissionKey = "{$resource}.{$ability}";
        $rolePermissions = static::permissionsForRole($role);

        return in_array($permissionKey, $rolePermissions, true);
    }

    protected static function permissionsForRole(string $role): array
    {
        if (! in_array($role, ['admin', 'member', 'owner'], true)) {
            return [];
        }

        $map = static::map();

        return $map[$role] ?? [];
    }

    protected static function map(): array
    {
        return [
            'admin' => Permission::pluck('key')->toArray(),
            'member' => array_filter(Permission::pluck('key')->toArray(), function ($key) {
                return str_contains($key, '.view');
            }),
        ];
    }

    public static function ensureDefaultRolesForOrganization(int $organizationId): void
    {
        $desired = ['owner', 'admin', 'member'];

        foreach ($desired as $name) {
            Role::firstOrCreate(
                ['organization_id' => $organizationId, 'name' => $name],
                ['organization_id' => $organizationId, 'name' => $name]
            );
        }

        $roles = Role::where('organization_id', $organizationId)->get()->keyBy('name');
        $allPermissions = Permission::pluck('id', 'key');

        $map = [
            'owner' => $allPermissions->values()->all(),
            'admin' => $allPermissions->values()->all(),
            'member' => $allPermissions->filter(fn ($id, $key) => str_contains($key, '.view'))->values()->all(),
        ];

        foreach ($map as $roleName => $permissionIds) {
            if (! isset($roles[$roleName])) {
                continue;
            }

            $roles[$roleName]->permissions()->sync($permissionIds);
        }
    }

    public static function syncUserRole(User $user, int $organizationId, string $roleName): void
    {
        static::ensureDefaultRolesForOrganization($organizationId);

        $role = Role::where('organization_id', $organizationId)->where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        DB::table('role_user')->updateOrInsert(
            [
                'role_id' => $role->id,
                'user_id' => $user->id,
                'organization_id' => $organizationId,
            ],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }
}
