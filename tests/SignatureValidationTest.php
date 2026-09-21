<?php

declare(strict_types=1);

/**
 * tests/SignatureValidationTest.php
 *
 * The existing EndpointTest covers the happy path and three rejections. This
 * file fills the most dangerous gaps: per-header validation, the edges of the
 * replay protection and whether the signature really covers the full payload.
 * This is the only security the package has, so every gap here exposes business
 * figures on the internet.
 */

use Schmeits\AppMetrics\Data\Metric;
use Schmeits\AppMetrics\Facades\AppMetrics;

test('endpoint rejects a request without a signature header', function () {
    $headers = signedHeaders();
    unset($headers['X-App-Metrics-Signature']);

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a request without a timestamp header', function () {
    $headers = signedHeaders();
    unset($headers['X-App-Metrics-Timestamp']);

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a request without a nonce header', function () {
    // The nonce is part of the signed payload. If it is no longer required, an
    // intercepted signature stays reusable for 60 seconds.
    $headers = signedHeaders();
    unset($headers['X-App-Metrics-Nonce']);

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a signature made with a different nonce', function () {
    // Proves that the nonce is genuinely part of the signature and does not just
    // need to be present. Without this, the nonce would be purely decorative.
    $headers = signedHeaders();
    $headers['X-App-Metrics-Nonce'] = bin2hex(random_bytes(16));

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a signature made with a different timestamp', function () {
    // Same for the timestamp: it must be part of the payload, otherwise an
    // attacker could stretch the expiry window themselves.
    $headers = signedHeaders();
    $headers['X-App-Metrics-Timestamp'] = (string) (time() - 30);

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a signature made with a different secret', function () {
    // The scenario of a dashboard knocking on the wrong app.
    $this->getJson('/api/metrics', signedHeaders('a-completely-different-secret'))
        ->assertForbidden();
});

test('endpoint rejects an incorrect signature of the correct length', function () {
    // hash_equals() compares in constant time, but also simply returns false for
    // different lengths. An equally long but wrong value tests the comparison
    // itself instead of the length check.
    $headers = signedHeaders();
    $headers['X-App-Metrics-Signature'] = hash('sha256', 'nonsense');

    $this->getJson('/api/metrics', $headers)->assertForbidden();
});

test('endpoint rejects a non-numeric timestamp', function () {
    // The timestamp is cast to int. Garbage becomes 0 and thus falls far outside
    // the window, so the request should be rejected rather than crash.
    $timestamp = 'yesterday';
    $nonce = bin2hex(random_bytes(16));

    $this->getJson('/api/metrics', [
        'X-App-Metrics-Signature' => hash_hmac('sha256', $timestamp.':'.$nonce, 'test-secret-key'),
        'X-App-Metrics-Timestamp' => $timestamp,
        'X-App-Metrics-Nonce' => $nonce,
    ])->assertForbidden();
});

test('endpoint accepts a timestamp just inside the 60 second window', function () {
    // The lower bound of the replay protection. If the window is ever shrunk,
    // this catches it before dashboards on slow networks start getting 403s.
    $this->getJson('/api/metrics', signedHeadersAt(time() - 59))
        ->assertOk();
});

test('endpoint rejects a timestamp just outside the 60 second window', function () {
    $this->getJson('/api/metrics', signedHeadersAt(time() - 61))
        ->assertForbidden();
});

test('endpoint rejects a timestamp too far in the future', function () {
    // abs() in the middleware ensures a clock running ahead is rejected too.
    // Without abs() an attacker could craft a signature that stays valid for
    // years.
    $this->getJson('/api/metrics', signedHeadersAt(time() + 3600))
        ->assertForbidden();
});

test('a valid request returns the registered metrics', function () {
    // The counterpart to all the rejections above: proof that the lock does open.
    AppMetrics::register(fn () => [Metric::string('status', 'ok')]);

    $this->getJson('/api/metrics', signedHeaders())
        ->assertOk()
        ->assertJsonPath('metrics.0.name', 'status')
        ->assertJsonPath('metrics.0.value', 'ok');
});

/**
 * Builds valid headers for a specific point in time.
 *
 * @return array<string, string>
 */
function signedHeadersAt(int $timestamp, string $secret = 'test-secret-key'): array
{
    $nonce = bin2hex(random_bytes(16));

    return [
        'X-App-Metrics-Signature' => hash_hmac('sha256', $timestamp.':'.$nonce, $secret),
        'X-App-Metrics-Timestamp' => (string) $timestamp,
        'X-App-Metrics-Nonce' => $nonce,
    ];
}
