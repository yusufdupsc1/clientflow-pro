<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'url',
        'events',
        'secret',
        'active',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function handles(string $event): bool
    {
        $events = $this->events ?? [];

        return in_array($event, $events, true);
    }
}
