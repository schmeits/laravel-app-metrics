<?php

declare(strict_types=1);

/**
 * tests/MetricTest.php
 *
 * Tests for the Metric DTO: factory methods, serialization, and track flag.
 */

use Schmeits\AppMetrics\Data\Metric;
use Schmeits\AppMetrics\Data\MetricType;

test('numeric metric serializes correctly', function () {
    $metric = Metric::numeric('active_users', 42, 'users');

    expect($metric->toArray())->toBe([
        'name' => 'active_users',
        'value' => 42,
        'type' => 'numeric',
        'group' => 'users',
        'suffix' => null,
        'tracked' => false,
        'tenant' => null,
    ]);
});

test('currency metric includes suffix', function () {
    $metric = Metric::currency('revenue', 1250.50, 'finance');

    expect($metric->toArray())
        ->name->toBe('revenue')
        ->value->toBe(1250.50)
        ->type->toBe('currency')
        ->suffix->toBe('EUR')
        ->tracked->toBeFalse();
});

test('percentage metric has percent suffix', function () {
    $metric = Metric::percentage('scan_rate', 87.5, 'operations');

    expect($metric->toArray())
        ->type->toBe('percentage')
        ->suffix->toBe('%');
});

test('string metric serializes correctly', function () {
    $metric = Metric::string('status', 'healthy', 'system');

    expect($metric->toArray())
        ->type->toBe('string')
        ->value->toBe('healthy');
});

test('track method sets tracked flag', function () {
    $metric = Metric::numeric('total_sales', 100, 'sales')->track();

    expect($metric->tracked)->toBeTrue();
    expect($metric->toArray()['tracked'])->toBeTrue();
});

test('metrics are not tracked by default', function () {
    $metric = Metric::numeric('count', 5);

    expect($metric->tracked)->toBeFalse();
    expect($metric->group)->toBeNull();
});

test('tenant method sets tenant name', function () {
    $metric = Metric::numeric('tickets', 42, 'sales')->tenant('Alfa Bier');

    expect($metric->tenant)->toBe('Alfa Bier');
    expect($metric->toArray()['tenant'])->toBe('Alfa Bier');
});

test('tenant is null by default', function () {
    $metric = Metric::numeric('count', 5);

    expect($metric->tenant)->toBeNull();
});

test('track and tenant can be chained', function () {
    $metric = Metric::numeric('tickets', 40, 'sales')->tenant('Brouwerij')->track();

    expect($metric->tenant)->toBe('Brouwerij');
    expect($metric->tracked)->toBeTrue();
});

test('metric type enum has correct values', function () {
    expect(MetricType::Numeric->value)->toBe('numeric');
    expect(MetricType::Currency->value)->toBe('currency');
    expect(MetricType::Percentage->value)->toBe('percentage');
    expect(MetricType::String->value)->toBe('string');
});
