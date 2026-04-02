<?php

declare(strict_types=1);

/**
 * src/Facades/AppMetrics.php
 *
 * Facade for the AppMetrics manager class.
 */

namespace Schmeits\AppMetrics\Facades;

use Illuminate\Support\Facades\Facade;
use Schmeits\AppMetrics\AppMetrics as AppMetricsManager;

/**
 * @method static \Schmeits\AppMetrics\AppMetrics register(\Closure $closure)
 * @method static array<int, \Schmeits\AppMetrics\Data\Metric> collect()
 *
 * @see AppMetricsManager
 */
class AppMetrics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppMetricsManager::class;
    }
}
