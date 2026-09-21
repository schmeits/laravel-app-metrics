<?php

declare(strict_types=1);

namespace Schmeits\AppMetrics\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Schmeits\AppMetrics\AppMetricsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // This package should never reach out to the network itself: it only
        // exposes an endpoint. The moment someone does add an outgoing call, the
        // relevant test fails here immediately instead of quietly calling an
        // external service in CI.
        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AppMetricsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app-metrics.secret', 'test-secret-key');
    }
}
