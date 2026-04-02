<?php

declare(strict_types=1);

/**
 * src/AppMetrics.php
 *
 * Manager class that holds the metrics registration closure.
 * Client apps register metrics via AppMetrics::register().
 */

namespace Schmeits\AppMetrics;

use Closure;
use Schmeits\AppMetrics\Data\Metric;

class AppMetrics
{
    protected ?Closure $metricsClosure = null;

    /**
     * Register a closure that returns an array of Metric instances.
     */
    public function register(Closure $closure): self
    {
        $this->metricsClosure = $closure;

        return $this;
    }

    /**
     * Execute the registered closure and return the metrics.
     *
     * @return array<int, Metric>
     */
    public function collect(): array
    {
        if (! $this->metricsClosure) {
            return [];
        }

        /** @var array<int, Metric> */
        return ($this->metricsClosure)();
    }
}
