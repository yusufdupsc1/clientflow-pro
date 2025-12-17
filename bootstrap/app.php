<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Console\Commands\DemoSeedCommand;
use App\Console\Commands\SendOverdueRemindersCommand;
use App\Console\Commands\MailTestCommand;
use App\Console\Commands\RepairCurrenciesCommand;
use App\Http\Middleware\ReadOnlyMode;
use App\Console\Commands\MarkOverdueInvoicesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Session\Middleware\AuthenticatesSessions;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
    ])
    ->withCommands([
        DemoSeedCommand::class,
        SendOverdueRemindersCommand::class,
        MailTestCommand::class,
        MarkOverdueInvoicesCommand::class,
        RepairCurrenciesCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(MarkOverdueInvoicesCommand::class)->dailyAt('01:00');
        $schedule->command(SendOverdueRemindersCommand::class)->dailyAt('02:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org.selected' => EnsureOrganizationSelected::class,
            'org' => EnsureOrganizationSelected::class,
            'read.only' => ReadOnlyMode::class,
        ]);

        // Ensure the tenant is resolved before implicit route model binding runs.
        $middleware->priority([
            EnsureFrontendRequestsAreStateful::class,
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            AuthenticatesRequests::class,
            EnsureEmailIsVerified::class,
            EnsureOrganizationSelected::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            AuthenticatesSessions::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
