<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'notes',
    ];
}
