<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'client_id',
        'project_id',
        'title',
        'notes',
        'invoice_number',
        'public_hash',
        'due_date',
        'sent_at',
        'paid_at',
        'status',
        'amount_paid_cents',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'tax_rate_percent',
        'total_cents',
        'currency',
<<<<<<< HEAD
        'stripe_product_id',
        'stripe_price_id',
        'stripe_price_amount_cents',
        'stripe_price_currency',
        'stripe_payment_link_id',
        'stripe_payment_link_url',
        'stripe_checkout_session_id',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_mode',
=======
        'tax_rate',
        'tax_cents',
        'discount_cents',
        'discount_type',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'payment_link_url',
        'payment_link_expires_at',
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
<<<<<<< HEAD
        'tax_rate_percent' => 'decimal:2',
=======
        'payment_link_expires_at' => 'datetime',
        'tax_rate' => 'decimal:2',
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
