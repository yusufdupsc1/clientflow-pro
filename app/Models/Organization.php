<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'billing_email',
        'tax_id',
        'default_currency',
        'stripe_mode',
        'stripe_live_secret',
        'stripe_live_publishable_key',
        'stripe_live_webhook_secret',
        'stripe_test_secret',
        'stripe_test_publishable_key',
        'stripe_test_webhook_secret',
        'branding_logo_path',
        'branding_color',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    // Future: clients(), projects(), invoices() etc.
}
