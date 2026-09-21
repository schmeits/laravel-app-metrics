<?php

declare(strict_types=1);

/**
 * tests/ServiceProviderSmokeTest.php
 *
 * Proves that the service provider registers and boots in a real (Testbench)
 * app, and that everything it wires up (config, binding, route, middleware)
 * actually exists. This is the first domino: if it falls, every project using
 * this package is broken before a single metric even exists.
 */

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Schmeits\AppMetrics\AppMetrics;
use Schmeits\AppMetrics\AppMetricsServiceProvider;
use Schmeits\AppMetrics\Facades\AppMetrics as AppMetricsFacade;
use Schmeits\AppMetrics\Http\Controllers\MetricsController;
use Schmeits\AppMetrics\Http\Middleware\ValidateMetricsSignature;

test('the service provider is registered in the container', function () {
    expect(app()->getProvider(AppMetricsServiceProvider::class))
        ->toBeInstanceOf(AppMetricsServiceProvider::class);
});

test('the config file that hasConfigFile promises exists on disk', function () {
    // hasConfigFile() derives the file name from the package name. If we rename
    // the file, merging fails silently and everything falls back to defaults.
    expect(is_file(__DIR__.'/../config/app-metrics.php'))->toBeTrue();
});

test('the config is merged into the app', function () {
    // Without the merge, config('app-metrics.url') would be null and the route
    // would land on the hardcoded fallback without anyone noticing.
    expect(config('app-metrics.url'))->toBe('/api/metrics')
        ->and(config('app-metrics.middleware'))->toBe(['api']);
});

test('the publish tag app-metrics-config from the README is registered', function () {
    // The README literally tells users to run
    // vendor:publish --tag="app-metrics-config".
    expect(ServiceProvider::$publishGroups)->toHaveKey('app-metrics-config');
});

test('every publishable config source points at an existing path', function () {
    $sources = array_keys(ServiceProvider::$publishGroups['app-metrics-config']);

    expect($sources)->not->toBeEmpty();

    foreach ($sources as $source) {
        expect(is_file($source))->toBeTrue("Publishable path does not exist: {$source}");
    }
});

test('AppMetrics is scoped and returns the same instance within a request', function () {
    // This is essential: apps call AppMetrics::register() in their own service
    // provider, and the controller reads the closure back out later. If the
    // binding is not shared, that registration is gone and the endpoint always
    // returns an empty list.
    expect(app(AppMetrics::class))->toBe(app(AppMetrics::class));
});

test('the facade points at the same instance as the container', function () {
    AppMetricsFacade::register(fn () => []);

    expect(AppMetricsFacade::getFacadeRoot())->toBe(app(AppMetrics::class));
});

test('the route is registered under the name app-metrics.endpoint', function () {
    // Consumers can use route('app-metrics.endpoint'). If the name disappears,
    // that breaks even though the endpoint itself still works.
    expect(Route::has('app-metrics.endpoint'))->toBeTrue();
});

test('the route lives on the url from the config', function () {
    expect(metricsRoute()->uri())->toBe('api/metrics');
});

test('the route only listens on GET', function () {
    // A metrics endpoint should be read-only.
    expect(metricsRoute()->methods())->toEqualCanonicalizing(['GET', 'HEAD']);
});

test('the route points at the MetricsController', function () {
    expect(metricsRoute()->getActionName())->toBe(MetricsController::class);
});

test('the signature middleware is attached to the route', function () {
    // This is the security of the entire package. If this middleware drops off
    // the route, all business figures are exposed on the internet without any
    // test turning red. Hence it is pinned down explicitly.
    expect(metricsRoute()->gatherMiddleware())->toContain(ValidateMetricsSignature::class);
});

test('the middleware from the config is attached to the route too', function () {
    expect(metricsRoute()->middleware())->toContain('api');
});

/**
 * Retrieves the registered metrics route.
 */
function metricsRoute(): RoutingRoute
{
    $route = Route::getRoutes()->getByName('app-metrics.endpoint');

    expect($route)->not->toBeNull('The route app-metrics.endpoint is not registered.');

    return $route;
}
