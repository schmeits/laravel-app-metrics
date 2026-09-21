<?php

declare(strict_types=1);

/**
 * tests/RouteConfigurationTest.php
 *
 * The README advertises "custom endpoint URL and middleware via config". That
 * promise was never tested anywhere: all existing tests run on the default
 * values, so a hardcoded '/api/metrics' in bootingPackage() would never be
 * noticed.
 *
 * The route is built while the app boots, so we cannot change the config after
 * the fact and then inspect the existing route. Instead we put a fresh Router
 * into the container, run bootingPackage() once more and look at what ends up
 * on it. This leaves the test app's real router untouched and avoids duplicate
 * routes.
 */

use Illuminate\Routing\Router;
use Schmeits\AppMetrics\AppMetricsServiceProvider;
use Schmeits\AppMetrics\Http\Controllers\MetricsController;
use Schmeits\AppMetrics\Http\Middleware\ValidateMetricsSignature;

/**
 * Rebuilds the metrics route with the current config, on an empty router.
 */
function bootRouteOnFreshRouter(): Router
{
    $router = new Router(app('events'), app());

    app()->instance(Router::class, $router);

    (new AppMetricsServiceProvider(app()))->bootingPackage();

    // ->name() is only set after the route is added to the collection, so the
    // name index has to be refreshed explicitly. In a real app Laravel does
    // that automatically when matching the first request.
    $router->getRoutes()->refreshNameLookups();

    return $router;
}

test('the route follows the custom url from the config', function () {
    config()->set('app-metrics.url', '/internal/figures');

    expect(bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint')->uri())
        ->toBe('internal/figures');
});

test('the custom middleware from the config is applied', function () {
    config()->set('app-metrics.middleware', ['web']);

    $route = bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint');

    expect($route->middleware())->toContain('web')
        ->and($route->middleware())->not->toContain('api');
});

test('the signature middleware stays in place even with custom middleware', function () {
    // The config middleware is merged with the signature middleware via the
    // spread operator. A mistake there could spread the security away without
    // breaking the route itself, leaving all business figures wide open.
    config()->set('app-metrics.middleware', []);

    expect(bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint')->gatherMiddleware())
        ->toContain(ValidateMetricsSignature::class);
});

test('the route falls back to api/metrics when the config url is missing', function () {
    // bootingPackage() has a fallback in case the config was not merged. That
    // fallback must keep working, otherwise booting the app crashes.
    config()->set('app-metrics.url', null);

    expect(bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint')->uri())
        ->toBe('api/metrics');
});

test('the route falls back to the api middleware when the config does not name it', function () {
    config()->set('app-metrics.middleware', null);

    $route = bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint');

    expect($route->middleware())->toContain('api')
        ->and($route->gatherMiddleware())->toContain(ValidateMetricsSignature::class);
});

test('the rebuilt route still points at the MetricsController', function () {
    expect(bootRouteOnFreshRouter()->getRoutes()->getByName('app-metrics.endpoint')->getActionName())
        ->toBe(MetricsController::class);
});
