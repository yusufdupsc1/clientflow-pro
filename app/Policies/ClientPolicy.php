<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Support\Permissions;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'clients', 'viewAny', $user->current_organization_id);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return $this->belongsToClientOrganization($user, $client) &&
            $this->can($user, 'clients', 'view', $client->organization_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->can($user, 'clients', 'create', $user->current_organization_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $this->belongsToClientOrganization($user, $client) &&
            $this->can($user, 'clients', 'update', $client->organization_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->belongsToClientOrganization($user, $client) &&
            $this->can($user, 'clients', 'delete', $client->organization_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $this->belongsToClientOrganization($user, $client) &&
            $this->can($user, 'clients', 'delete', $client->organization_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $this->belongsToClientOrganization($user, $client) &&
            $this->can($user, 'clients', 'delete', $client->organization_id);
    }

    protected function belongsToClientOrganization(User $user, Client $client): bool
    {
        return $user->organizations()->whereKey($client->organization_id)->exists();
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
