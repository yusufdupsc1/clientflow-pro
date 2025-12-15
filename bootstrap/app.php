<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Console\Commands\DemoSeedCommand;
use App\Console\Commands\SendOverdueRemindersCommand;
use App\Http\Middleware\ReadOnlyMode;

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
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org.selected' => EnsureOrganizationSelected::class,
            'org' => EnsureOrganizationSelected::class,
            'read.only' => ReadOnlyMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
