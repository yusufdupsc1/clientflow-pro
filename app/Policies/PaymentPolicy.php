<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function create(User $user, Payment $payment): bool
    {
        return $user->organizations()->whereKey($payment->invoice->organization_id)->exists();
    }
}
