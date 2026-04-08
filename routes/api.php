<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InterventionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Gestion de Parc Informatique
|--------------------------------------------------------------------------
|
| Toutes les routes sont préfixées par /api (voir bootstrap/app.php ou
| RouteServiceProvider).
|
*/

// ══════════════════════════════════════════════════════════════
//  AUTHENTIFICATION (publique)
// ══════════════════════════════════════════════════════════════
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login',    [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');

    // Routes protégées par JWT
    Route::middleware('auth:api')->group(function () {
        Route::get ('me',      [AuthController::class, 'me'])->name('me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('logout',  [AuthController::class, 'logout'])->name('logout');
    });
});

// ══════════════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES PAR JWT
// ══════════════════════════════════════════════════════════════
Route::middleware('auth:api')->group(function () {

    // ─── Dashboard ──────────────────────────────────────────
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // ─── Tickets ────────────────────────────────────────────
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get   ('/',                      [TicketController::class, 'index'])->name('index');
        Route::post  ('/',                      [TicketController::class, 'store'])->name('store');
        Route::get   ('/{id}',                  [TicketController::class, 'show'])->name('show');
        Route::put   ('/{id}',                  [TicketController::class, 'update'])->name('update');
        Route::delete('/{id}',                  [TicketController::class, 'destroy'])->name('destroy');
        Route::patch ('/{id}/status',           [TicketController::class, 'changeStatus'])->name('status');
        Route::post  ('/{id}/attachments',      [TicketController::class, 'uploadAttachment'])->name('attachments.upload');

        // Admin only
        Route::post('/{id}/assign', [TicketController::class, 'assign'])
            ->middleware('role:admin')
            ->name('assign');
    });

    // ─── Interventions ──────────────────────────────────────
    Route::prefix('interventions')->name('interventions.')->group(function () {
        Route::get   ('/',                  [InterventionController::class, 'index'])->name('index');
        Route::post  ('/',                  [InterventionController::class, 'store'])->name('store');
        Route::get   ('/{id}',              [InterventionController::class, 'show'])->name('show');
        Route::put   ('/{id}',              [InterventionController::class, 'update'])->name('update');
        Route::delete('/{id}',              [InterventionController::class, 'destroy'])->name('destroy');
        Route::patch ('/{id}/start',        [InterventionController::class, 'start'])->name('start');
        Route::patch ('/{id}/complete',     [InterventionController::class, 'complete'])->name('complete');
        Route::post  ('/{id}/attachments',  [InterventionController::class, 'uploadAttachment'])->name('attachments.upload');
    });

    // ─── Stock & Affectations ───────────────────────────────
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/',                [StockController::class, 'index'])->name('index');
        Route::get('/next-reference',  [StockController::class, 'nextReference'])->middleware('role:admin')->name('next-reference');
        Route::get('/low-stock',       [StockController::class, 'lowStock'])->middleware('role:admin')->name('low-stock');
        Route::get('/{id}',            [StockController::class, 'show'])->name('show');

        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post  ('/',                                      [StockController::class, 'store'])->name('store');
            Route::put   ('/{id}',                                  [StockController::class, 'update'])->name('update');
            Route::delete('/{id}',                                  [StockController::class, 'destroy'])->name('destroy');
            Route::post  ('/{id}/assign',                           [StockController::class, 'assign'])->name('assign');
            Route::patch ('/affectations/{affectationId}/return',   [StockController::class, 'returnStock'])->name('return');
        });
    });

    // ─── Utilisateurs ───────────────────────────────────────
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get   ('/',                      [UserController::class, 'index'])->name('index');
        Route::post  ('/',                      [UserController::class, 'store'])->name('store');
        Route::get   ('/technicians',           [UserController::class, 'technicians'])->name('technicians');
        Route::get   ('/{id}',                  [UserController::class, 'show'])->name('show');
        Route::put   ('/{id}',                  [UserController::class, 'update'])->name('update');
        Route::delete('/{id}',                  [UserController::class, 'destroy'])->name('destroy');
        Route::patch ('/{id}/toggle-active',    [UserController::class, 'toggleActive'])->name('toggle-active');
    });

    // ─── Pièces jointes ─────────────────────────────────────
    Route::prefix('attachments')->name('attachments.')->group(function () {
        Route::get   ('/{id}/download', [AttachmentController::class, 'download'])->name('download');
        Route::delete('/{id}',          [AttachmentController::class, 'destroy'])->name('destroy');
    });

    // ─── Notifications ───────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get   ('/',          [NotificationController::class, 'index'])->name('index');
        Route::patch ('/read-all',  [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::patch ('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::delete('/{id}',      [NotificationController::class, 'destroy'])->name('destroy');
    });
});
