<?php

declare(strict_types=1);

/**
 * src/AppMetricsServiceProvider.php
 *
 * Registers the AppMetrics package: config, route, and facade binding.
 */

namespace Schmeits\AppMetrics;

use Schmeits\AppMetrics\Http\Controllers\MetricsController;
use Schmeits\AppMetrics\Http\Middleware\ValidateMetricsSignature;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AppMetricsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('app-metrics')
            ->hasConfigFile();
    }

    public function registeringPackage(): void
    {
        $this->app->scoped(AppMetrics::class);
    }

    public function bootingPackage(): void
    {
        $url = config('app-metrics.url', '/api/metrics');
        $middleware = config('app-metrics.middleware', ['api']);

        $this->app['router']
            ->middleware([...$middleware, ValidateMetricsSignature::class])
            ->get($url, MetricsController::class)
            ->name('app-metrics.endpoint');
    }
}
