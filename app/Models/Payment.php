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
        'currency',
        'refunded_cents',
        'paid_at',
        'refunded_at',
        'method',
        'reference',
        'provider',
        'external_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_refund_id',
        'stripe_balance_transaction_id',
        'stripe_receipt_url',
        'stripe_webhook_event_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
