<?php

declare(strict_types=1);

/**
 * tests/EndpointTest.php
 *
 * Feature tests for the /api/metrics endpoint: HMAC validation and response format.
 */

use Schmeits\AppMetrics\Data\Metric;
use Schmeits\AppMetrics\Facades\AppMetrics;

function signedHeaders(string $secret = 'test-secret-key'): array
{
    $timestamp = (string) time();
    $nonce = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', $timestamp.':'.$nonce, $secret);

    return [
        'X-App-Metrics-Signature' => $signature,
        'X-App-Metrics-Timestamp' => $timestamp,
        'X-App-Metrics-Nonce' => $nonce,
    ];
}

test('endpoint returns metrics with valid signature', function () {
    AppMetrics::register(fn () => [
        Metric::numeric('users', 42, 'system'),
        Metric::currency('revenue', 1000.00, 'finance')->track(),
    ]);

    $response = $this->getJson('/api/metrics', signedHeaders());

    $response->assertOk()
        ->assertJsonCount(2, 'metrics')
        ->assertJsonPath('metrics.0.name', 'users')
        ->assertJsonPath('metrics.0.tracked', false)
        ->assertJsonPath('metrics.1.name', 'revenue')
        ->assertJsonPath('metrics.1.tracked', true)
        ->assertJsonStructure([
            'app',
            'timestamp',
            'metrics' => [
                '*' => ['name', 'value', 'type', 'group', 'suffix', 'tracked', 'tenant'],
            ],
        ]);
});

test('endpoint rejects request without signature headers', function () {
    $response = $this->getJson('/api/metrics');

    $response->assertForbidden();
});

test('endpoint rejects request with invalid signature', function () {
    $headers = signedHeaders();
    $headers['X-App-Metrics-Signature'] = 'invalid-signature';

    $response = $this->getJson('/api/metrics', $headers);

    $response->assertForbidden();
});

test('endpoint rejects expired timestamp', function () {
    $timestamp = (string) (time() - 120); // 2 minutes ago
    $nonce = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', $timestamp.':'.$nonce, 'test-secret-key');

    $response = $this->getJson('/api/metrics', [
        'X-App-Metrics-Signature' => $signature,
        'X-App-Metrics-Timestamp' => $timestamp,
        'X-App-Metrics-Nonce' => $nonce,
    ]);

    $response->assertForbidden();
});

test('endpoint returns empty array when no metrics registered', function () {
    $response = $this->getJson('/api/metrics', signedHeaders());

    $response->assertOk()
        ->assertJsonCount(0, 'metrics');
});
