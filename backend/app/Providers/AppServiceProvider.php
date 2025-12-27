<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\SupplierRepositoryInterface;
use App\Infrastructure\Persistence\EloquentSupplierRepository;
use App\Domain\Services\AuditServiceInterface;
use App\Infrastructure\Services\EloquentAuditService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository implementations
        // This binds interfaces to concrete implementations (Dependency Inversion Principle)
        $this->app->bind(SupplierRepositoryInterface::class, EloquentSupplierRepository::class);
        
        // Register service implementations
        $this->app->bind(AuditServiceInterface::class, EloquentAuditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
