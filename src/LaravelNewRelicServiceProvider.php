<?php

namespace MarothyZsolt\LaravelNewRelic;

use Illuminate\Support\ServiceProvider;
use MarothyZsolt\LaravelNewRelic\Logging\NewRelicLogger;

class LaravelNewRelicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('newrelic.php'),
            ], 'config');
        }

        $this->app->singleton(NewRelicLogger::class);
    }

    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'newrelic');
    }
}
