<?php

declare(strict_types=1);

/**
 * src/Http/Controllers/MetricsController.php
 *
 * Invokable controller that returns registered metrics as JSON.
 */

namespace Schmeits\AppMetrics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schmeits\AppMetrics\AppMetrics;
use Schmeits\AppMetrics\Data\Metric;

class MetricsController
{
    public function __invoke(AppMetrics $appMetrics): JsonResponse
    {
        $metrics = $appMetrics->collect();

        return response()->json([
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'metrics' => array_map(fn (Metric $m) => $m->toArray(), $metrics),
        ]);
    }
}
