<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Permissions;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'invoices', 'viewAny', $user->current_organization_id);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice)
            && $this->can($user, 'invoices', 'view', $invoice->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'invoices', 'create', $user->current_organization_id);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice)
            && $this->can($user, 'invoices', 'update', $invoice->organization_id);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice)
            && $this->can($user, 'invoices', 'delete', $invoice->organization_id);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice)
            && $this->can($user, 'invoices', 'delete', $invoice->organization_id);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice)
            && $this->can($user, 'invoices', 'delete', $invoice->organization_id);
    }

    protected function belongsToInvoiceOrganization(User $user, Invoice $invoice): bool
    {
        return $user->organizations()->whereKey($invoice->organization_id)->exists();
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
