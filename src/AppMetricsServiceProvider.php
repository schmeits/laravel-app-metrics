<?php

declare(strict_types=1);

/**
 * src/AppMetricsServiceProvider.php
 *
 * Registers the AppMetrics package: config, route, and facade binding.
 */

namespace Schmeits\AppMetrics;

use Illuminate\Routing\Router;
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
        // Note: the second argument to config() only applies when the KEY is
        // missing, not when the value is null. A published config where a key
        // stays empty (or points at an empty env variable) therefore returned
        // null. For the middleware that meant [...null], and thus a fatal
        // TypeError during booting that took the whole application down. Hence
        // the explicit null handling below.

        /** @var string $url */
        $url = config('app-metrics.url') ?: '/api/metrics';

        /** @var array<int, string> $middleware */
        $middleware = config('app-metrics.middleware') ?? ['api'];

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router
            ->middleware([...$middleware, ValidateMetricsSignature::class])
            ->get($url, MetricsController::class)
            ->name('app-metrics.endpoint');
    }
}
