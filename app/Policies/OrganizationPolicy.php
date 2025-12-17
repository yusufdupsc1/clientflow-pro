<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;

class OrganizationPolicy
{
    public function manageMembers(User $user, Organization $organization): bool
    {
        $role = Permissions::roleFor($user, $organization->id);

        return in_array($role, ['owner', 'admin'], true);
    }

    public function manageSettings(User $user, Organization $organization): bool
    {
        return $this->manageMembers($user, $organization);
    }
}
