<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'projects', 'viewAny', $user->current_organization_id);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project)
            && $this->can($user, 'projects', 'view', $project->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'projects', 'create', $user->current_organization_id);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project)
            && $this->can($user, 'projects', 'update', $project->organization_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project)
            && $this->can($user, 'projects', 'delete', $project->organization_id);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project)
            && $this->can($user, 'projects', 'delete', $project->organization_id);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->belongsToProjectOrganization($user, $project)
            && $this->can($user, 'projects', 'delete', $project->organization_id);
    }

    protected function belongsToProjectOrganization(User $user, Project $project): bool
    {
        return $user->organizations()->whereKey($project->organization_id)->exists();
    }

    protected function can(User $user, string $resource, string $ability, ?int $organizationId): bool
    {
        if (! $organizationId) {
            return false;
        }

        $role = Permissions::roleFor($user, $organizationId);

        return Permissions::allows($role, $resource, $ability);
    }
}
