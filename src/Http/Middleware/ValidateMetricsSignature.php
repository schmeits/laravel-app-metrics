<?php

declare(strict_types=1);

/**
 * src/Http/Middleware/ValidateMetricsSignature.php
 *
 * HMAC-SHA256 signature validation middleware.
 * Validates timestamp (replay protection) and signature authenticity.
 */

namespace Schmeits\AppMetrics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateMetricsSignature
{
    /** Maximum age of a request in seconds (replay protection). */
    private const MAX_AGE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app-metrics.secret');

        if (! $secret) {
            abort(403, 'App metrics secret not configured.');
        }

        $signature = $request->header('X-App-Metrics-Signature');
        $timestamp = $request->header('X-App-Metrics-Timestamp');
        $nonce = $request->header('X-App-Metrics-Nonce');

        if (! $signature || ! $timestamp || ! $nonce) {
            abort(403, 'Missing authentication headers.');
        }

        // Replay protection: reject requests older than MAX_AGE_SECONDS
        if (abs(time() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            abort(403, 'Request timestamp expired.');
        }

        // Validate HMAC signature
        $payload = $timestamp.':'.$nonce;
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($computedSignature, $signature)) {
            abort(403, 'Invalid signature.');
        }

        return $next($request);
    }
}
