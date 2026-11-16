<?php

declare(strict_types=1);

/**
 * tests/EventsEndpointTest.php
 *
 * Feature tests for the /api/metrics/events endpoint: signature, the `since`
 * window (default 24 hours, at most 7 days), ordering and limit.
 */

use Carbon\CarbonImmutable;
use Schmeits\AppMetrics\Data\Event;
use Schmeits\AppMetrics\Facades\AppMetrics;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-13 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('events endpoint requires a valid signature', function () {
    $this->getJson('/api/metrics/events')->assertForbidden();
});

test('events endpoint returns events since the given moment, newest first', function () {
    AppMetrics::events(fn (CarbonImmutable $since) => [
        Event::make('order:1', CarbonImmutable::parse('2026-11-13 09:00'), '2 tickets sold')->type('sale')->tenant('Het huis van Bosch'),
        Event::make('order:2', CarbonImmutable::parse('2026-11-13 11:00'), '1 ticket sold')->type('sale')->meta(['amount' => 1]),
        Event::make('order:0', CarbonImmutable::parse('2026-11-12 08:00'), 'too old'),
    ]);

    $this->getJson('/api/metrics/events?since='.urlencode('2026-11-13T00:00:00+00:00'), signedHeaders())
        ->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.0.id', 'order:2')
        ->assertJsonPath('events.0.meta.amount', 1)
        ->assertJsonPath('events.1.tenant', 'Het huis van Bosch')
        ->assertJsonPath('events.1.type', 'sale')
        ->assertJsonMissingPath('events.1.meta');
});

test('since defaults to 24 hours and is clamped to 7 days back', function () {
    $ontvangen = [];
    AppMetrics::events(function (CarbonImmutable $since) use (&$ontvangen) {
        $ontvangen[] = $since->toDateTimeString();

        return [];
    });

    $this->getJson('/api/metrics/events', signedHeaders())->assertOk();
    $this->getJson('/api/metrics/events?since=2020-01-01', signedHeaders())->assertOk();

    expect($ontvangen)->toBe(['2026-11-12 12:00:00', '2026-11-06 12:00:00']);
});

test('limit caps the number of events', function () {
    AppMetrics::events(fn () => array_map(
        fn (int $i) => Event::make("e:{$i}", CarbonImmutable::now()->subMinutes($i), "event {$i}"),
        range(1, 5),
    ));

    $this->getJson('/api/metrics/events?limit=2', signedHeaders())
        ->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.0.id', 'e:1');
});

test('without registered events the endpoint returns an empty list', function () {
    $this->getJson('/api/metrics/events', signedHeaders())
        ->assertOk()
        ->assertJsonPath('events', []);
});

test('an unreadable since falls back to the last 24 hours', function () {
    AppMetrics::events(fn (CarbonImmutable $since) => [
        Event::make('recent', CarbonImmutable::parse('2026-11-13 10:00'), 'within a day'),
        Event::make('old', CarbonImmutable::parse('2026-11-11 10:00'), 'two days old'),
    ]);

    $this->getJson('/api/metrics/events?since=not-a-date', signedHeaders())
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.id', 'recent');
});
