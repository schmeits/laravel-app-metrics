<?php

declare(strict_types=1);

/**
 * src/AppMetrics.php
 *
 * Manager class that holds the metrics registration closure.
 * Client apps register metrics via AppMetrics::register() and, optionally,
 * a timeline of events via AppMetrics::events().
 */

namespace Schmeits\AppMetrics;

use Carbon\CarbonImmutable;
use Closure;
use Schmeits\AppMetrics\Data\Event;
use Schmeits\AppMetrics\Data\Metric;

class AppMetrics
{
    protected ?Closure $metricsClosure = null;

    protected ?Closure $eventsClosure = null;

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

    /**
     * Register a closure that receives the moment to look back to and returns
     * an array of Event instances that happened since then.
     *
     * @param  Closure(CarbonImmutable): array<int, Event>  $closure
     */
    public function events(Closure $closure): self
    {
        $this->eventsClosure = $closure;

        return $this;
    }

    /**
     * @return array<int, Event>
     */
    public function collectEvents(CarbonImmutable $since): array
    {
        if (! $this->eventsClosure) {
            return [];
        }

        /** @var array<int, Event> */
        return ($this->eventsClosure)($since);
    }
}
