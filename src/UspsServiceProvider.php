<?php // src/UspsServiceProvider.php

namespace m4dev\UspsShip;

use Illuminate\Support\ServiceProvider;
use m4dev\UspsShip\Http\HttpClient;
use m4dev\UspsShip\Services\{RateService, LabelService, TrackingService};

class UspsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/usps.php', 'usps');

        $this->app->singleton(HttpClient::class, function ($app) {
            return new HttpClient(config('usps'));
        });

        $this->app->bind(RateService::class, fn($app) => new RateService(config('usps')));
        $this->app->bind(LabelService::class, fn($app) => new LabelService($app->make(HttpClient::class), config('usps')));
        $this->app->bind(TrackingService::class, fn($app) => new TrackingService($app->make(HttpClient::class), config('usps')));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/usps.php' => config_path('usps.php'),
        ], 'config');
        $this->loadRoutesFrom(__DIR__ . '/../routes/usps-webhooks.php');
    }
}
