<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null
            && $user->organizations()->whereKey($user->current_organization_id)->exists();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null
            && $user->organizations()->whereKey($user->current_organization_id)->exists();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $this->belongsToInvoiceOrganization($user, $invoice);
    }

    protected function belongsToInvoiceOrganization(User $user, Invoice $invoice): bool
    {
        return $user->organizations()->whereKey($invoice->organization_id)->exists();
    }
}
