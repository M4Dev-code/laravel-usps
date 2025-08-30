<?php

namespace UspsShipping\Laravel;

use Illuminate\Support\ServiceProvider;

class UspsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('usps', function ($app) {
            return new UspsClient($app['config']['usps']);
        });

        $this->app->bind(Services\AddressValidationService::class, function ($app) {
            return new Services\AddressValidationService($app['usps']);
        });

        $this->app->bind(Services\RateService::class, function ($app) {
            return new Services\RateService($app['usps'], $app['config']['usps']);
        });

        $this->app->bind(Services\LabelService::class, function ($app) {
            return new Services\LabelService($app['usps'], $app['config']['usps']);
        });

        $this->app->bind(Services\TrackingService::class, function ($app) {
            return new Services\TrackingService($app['usps']);
        });

        $this->app->bind(Services\UspsShippingService::class, function ($app) {
            return new Services\UspsShippingService($app['usps']);
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/usps.php', 'usps');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/usps.php' => config_path('usps.php'),
        ], 'usps-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TestUspsConnection::class,
                Commands\UpdateTrackingCommand::class,
                Commands\CleanupCacheCommand::class,
            ]);
        }
    }
}
