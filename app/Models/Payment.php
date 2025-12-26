<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'amount_cents',
<<<<<<< HEAD
        'currency',
        'refunded_cents',
=======
        'refunded_cents',
        'refund_reason',
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
        'paid_at',
        'refunded_at',
        'method',
        'reference',
        'provider',
        'external_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
<<<<<<< HEAD
        'stripe_refund_id',
        'stripe_balance_transaction_id',
        'stripe_receipt_url',
        'stripe_webhook_event_id',
=======
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    ];

    protected $casts = [
        'paid_at' => 'datetime',
<<<<<<< HEAD
        'refunded_at' => 'datetime',
=======
        'refunded_cents' => 'integer',
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get net amount after refunds.
     */
    public function getNetAmountCentsAttribute(): int
    {
        return $this->amount_cents - ($this->refunded_cents ?? 0);
    }

    /**
     * Check if fully refunded.
     */
    public function getIsRefundedAttribute(): bool
    {
        return $this->refunded_cents >= $this->amount_cents;
    }
}
