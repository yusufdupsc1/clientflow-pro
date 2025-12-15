<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Web\InvitationController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Api\InvitationApiController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\InvoiceApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Web\OrganizationSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthController::class)->name('health');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'org.selected'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/organizations/select', 'organizations.select')->name('organizations.select');
    Route::post('/organizations/select', [OrganizationSelectionController::class, 'store'])->name('organizations.select.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'org'])->group(function () {
    Route::get('invoices/export', [ExportController::class, 'invoices'])->name('invoices.export');
    Route::get('payments/export', [ExportController::class, 'payments'])->name('payments.export');
    Route::resource('clients', ClientController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');

    Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('organizations/{organization}/invites', [InvitationController::class, 'store'])->name('organizations.invites.store');
    Route::patch('organizations/{organization}/members/{user}', [InvitationController::class, 'updateMemberRole'])->name('organizations.members.update');

    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
});

Route::post('invites/{token}', [InvitationController::class, 'accept'])->name('invites.accept');

Route::prefix('api')->group(function () {
    Route::middleware(['auth:sanctum', 'org'])->group(function () {
        Route::post('organizations/{organization}/invites', [InvitationApiController::class, 'store']);
        Route::patch('organizations/{organization}/members/{user}', [InvitationApiController::class, 'updateMemberRole']);
        Route::apiResource('clients', ClientApiController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('projects', ProjectApiController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('invoices', InvoiceApiController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('invoices/{invoice}/payments', [PaymentApiController::class, 'store']);
    });

    Route::post('invites/{token}', [InvitationApiController::class, 'accept']);

    Route::middleware(['auth'])->group(function () {
        Route::post('tokens', [TokenController::class, 'store']);
        Route::get('tokens', [TokenController::class, 'index']);
        Route::delete('tokens/{token}', [TokenController::class, 'destroy']);
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('tokens', [TokenController::class, 'webIndex'])->name('tokens.index');
    Route::delete('tokens/{token}', [TokenController::class, 'webDestroy'])->name('tokens.destroy');
});

require __DIR__.'/auth.php';
