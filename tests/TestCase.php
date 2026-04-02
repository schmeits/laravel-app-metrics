<?php

declare(strict_types=1);

namespace Schmeits\AppMetrics\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Schmeits\AppMetrics\AppMetricsServiceProvider;

class TestCase extends Orchestra
{
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
