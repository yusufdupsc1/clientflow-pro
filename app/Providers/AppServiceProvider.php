<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('client', fn (string $value) => Client::withoutGlobalScopes()->whereKey($value)->firstOrFail());
        Route::bind('project', fn (string $value) => Project::withoutGlobalScopes()->whereKey($value)->firstOrFail());
        Route::bind('invoice', fn (string $value) => Invoice::withoutGlobalScopes()->whereKey($value)->firstOrFail());
        Route::bind('payment', fn (string $value) => Payment::withoutGlobalScopes()->whereKey($value)->firstOrFail());

        Event::listen(Login::class, function (Login $event): void {
            Log::info('user.login', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]);
        });
    }
}
