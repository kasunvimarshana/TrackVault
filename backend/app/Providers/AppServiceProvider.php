<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\SupplierRepositoryInterface;
use App\Infrastructure\Persistence\EloquentSupplierRepository;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
