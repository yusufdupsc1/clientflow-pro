<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null
            && $user->organizations()->whereKey($user->current_organization_id)->exists();
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null
            && $user->organizations()->whereKey($user->current_organization_id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project);
    }

    protected function belongsToProjectOrganization(User $user, Project $project): bool
    {
        return $user->organizations()->whereKey($project->organization_id)->exists();
    }
}
