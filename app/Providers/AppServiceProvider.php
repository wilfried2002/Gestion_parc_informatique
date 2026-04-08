<?php

namespace App\Providers;

use App\Repositories\InterventionRepository;
use App\Repositories\StockRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogService;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\InterventionService;
use App\Services\StockService;
use App\Services\TicketService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ─── Repositories ──────────────────────────────────────────────
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(TicketRepository::class);
        $this->app->singleton(InterventionRepository::class);
        $this->app->singleton(StockRepository::class);

        // ─── Services ───────────────────────────────────────────────────
        $this->app->singleton(ActivityLogService::class);
        $this->app->singleton(AttachmentService::class);

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(UserRepository::class),
                $app->make(ActivityLogService::class)
            );
        });

        $this->app->singleton(TicketService::class, function ($app) {
            return new TicketService(
                $app->make(TicketRepository::class),
                $app->make(ActivityLogService::class)
            );
        });

        $this->app->singleton(InterventionService::class, function ($app) {
            return new InterventionService(
                $app->make(InterventionRepository::class),
                $app->make(TicketRepository::class),
                $app->make(ActivityLogService::class)
            );
        });

        $this->app->singleton(StockService::class, function ($app) {
            return new StockService(
                $app->make(StockRepository::class),
                $app->make(ActivityLogService::class)
            );
        });

        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserRepository::class),
                $app->make(ActivityLogService::class)
            );
        });

        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService(
                $app->make(TicketRepository::class),
                $app->make(InterventionRepository::class),
                $app->make(StockRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
