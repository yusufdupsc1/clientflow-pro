<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Payment;
use App\Policies\ClientPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Client::class => ClientPolicy::class,
        Project::class => ProjectPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
