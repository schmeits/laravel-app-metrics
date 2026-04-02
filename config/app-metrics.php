<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Shared Secret
    |--------------------------------------------------------------------------
    |
    | The HMAC shared secret used to sign and validate metric requests.
    | Each app should have a unique secret shared with the dashboard.
    |
    */
    'secret' => env('APP_METRICS_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint URL
    |--------------------------------------------------------------------------
    |
    | The URL path where the metrics endpoint will be registered.
    |
    */
    'url' => '/api/metrics',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Additional middleware to apply to the metrics endpoint.
    |
    */
    'middleware' => ['api'],
];
