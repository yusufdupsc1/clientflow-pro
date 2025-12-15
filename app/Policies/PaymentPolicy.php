<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Support\Permissions;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        $orgId = $user->current_organization_id;
        $role = Permissions::roleFor($user, $orgId);

        return Permissions::allows($role, 'payments', 'viewAny');
    }

    public function create(User $user, Payment $payment): bool
    {
        return $user->organizations()->whereKey($payment->invoice->organization_id)->exists()
            && $this->can($user, 'payments', 'create', $payment->invoice->organization_id);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->organizations()->whereKey($payment->invoice->organization_id)->exists()
            && $this->can($user, 'payments', 'view', $payment->invoice->organization_id);
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
