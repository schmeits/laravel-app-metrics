<?php

declare(strict_types=1);

/**
 * src/Http/Controllers/EventsController.php
 *
 * Invokable controller that returns the events since a given moment as JSON,
 * newest first. `since` is an ISO 8601 timestamp; it defaults to 24 hours ago
 * and is clamped to at most 7 days back. At most `limit` events (default 200,
 * max 500) are returned.
 */

namespace Schmeits\AppMetrics\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Schmeits\AppMetrics\AppMetrics;
use Schmeits\AppMetrics\Data\Event;
use Throwable;

class EventsController
{
    public const MAX_DAYS_BACK = 7;

    public const MAX_LIMIT = 500;

    public function __invoke(Request $request, AppMetrics $appMetrics): JsonResponse
    {
        $earliest = CarbonImmutable::now()->subDays(self::MAX_DAYS_BACK);

        try {
            $since = CarbonImmutable::parse((string) $request->query('since', CarbonImmutable::now()->subDay()->toIso8601String()));
        } catch (Throwable) {
            $since = CarbonImmutable::now()->subDay();
        }

        $since = $since->lessThan($earliest) ? $earliest : $since;
        $limit = max(1, min(self::MAX_LIMIT, (int) $request->query('limit', 200)));

        $events = collect($appMetrics->collectEvents($since))
            ->filter(fn (Event $event): bool => $event->at >= $since)
            ->sortByDesc(fn (Event $event): int => $event->at->getTimestamp())
            ->take($limit)
            ->values();

        return response()->json([
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'since' => $since->toIso8601String(),
            'events' => $events->map(fn (Event $event): array => $event->toArray())->all(),
        ]);
    }
}
