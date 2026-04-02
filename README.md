# Laravel App Metrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schmeits/laravel-app-metrics.svg?style=flat-square)](https://packagist.org/packages/schmeits/laravel-app-metrics)
[![Tests](https://img.shields.io/github/actions/workflow/status/schmeits/laravel-app-metrics/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/schmeits/laravel-app-metrics/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/schmeits/laravel-app-metrics.svg?style=flat-square)](https://packagist.org/packages/schmeits/laravel-app-metrics)

Expose business metrics from your Laravel application via a secure HMAC-signed JSON endpoint. Designed to be consumed by a central dashboard that aggregates metrics from multiple apps.

## Features

- **HMAC-SHA256 authentication** with replay protection (nonce + timestamp)
- **Typed metric DTOs** — numeric, currency, percentage, and string
- **Multi-tenant support** — tag metrics with a tenant name
- **Tracking flag** — mark metrics for daily snapshot tracking on the dashboard
- **Configurable** — custom endpoint URL, middleware, and shared secret via environment variable
- **Zero dependencies** beyond Laravel itself (and `spatie/laravel-package-tools`)

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require schmeits/laravel-app-metrics
```

Publish the config file:

```bash
php artisan vendor:publish --tag="app-metrics-config"
```

Add the shared secret to your `.env`:

```env
APP_METRICS_SECRET=your-shared-secret-here
```

> Generate a strong secret, for example: `openssl rand -hex 32`

## Configuration

The published config file (`config/app-metrics.php`) contains:

```php
return [
    // HMAC shared secret (required)
    'secret' => env('APP_METRICS_SECRET'),

    // Endpoint URL path
    'url' => '/api/metrics',

    // Middleware applied to the endpoint
    'middleware' => ['api'],
];
```

## Usage

### Registering metrics

Register your metrics in a service provider (e.g. `AppServiceProvider`):

```php
use Schmeits\AppMetrics\Facades\AppMetrics;
use Schmeits\AppMetrics\Data\Metric;

public function boot(): void
{
    AppMetrics::register(fn () => [
        Metric::numeric('active_users', User::where('active', true)->count(), 'users'),
        Metric::currency('monthly_revenue', Order::thisMonth()->sum('total'), 'finance', 'EUR'),
        Metric::percentage('conversion_rate', $this->calculateConversionRate(), 'marketing'),
        Metric::string('deploy_status', 'healthy', 'system'),
    ]);
}
```

### Metric types

| Factory method | Value type | Default suffix | Example |
|---|---|---|---|
| `Metric::numeric()` | `int\|float` | — | Active users, order count |
| `Metric::currency()` | `float` | `EUR` | Revenue, costs |
| `Metric::percentage()` | `float` | `%` | Conversion rate, uptime |
| `Metric::string()` | `string` | — | Status, version |

### Tracking metrics

Mark metrics for daily snapshot tracking on the dashboard:

```php
Metric::numeric('total_orders', Order::count(), 'sales')->track();
```

### Multi-tenant support

Tag metrics with a tenant name for multi-tenant applications:

```php
Metric::numeric('tickets', 42, 'support')->tenant('Acme Corp');
```

Methods can be chained:

```php
Metric::currency('revenue', 15000.00, 'finance')
    ->tenant('Acme Corp')
    ->track();
```

### Metadata

Attach arbitrary display metadata (label, description, color, etc.) to any metric using the `meta()` method:

```php
Metric::numeric('scanned_today', 100, 'sales')
    ->meta(['label' => '100 gescand', 'description' => '+20 ten opzichte van gisteren']);
```

Multiple `meta()` calls merge the data, allowing progressive enrichment:

```php
Metric::numeric('tickets', 42, 'support')
    ->meta(['label' => '42 tickets'])
    ->meta(['color' => '#22c55e']);
// Result: ['label' => '42 tickets', 'color' => '#22c55e']
```

Meta is only included in the JSON output when non-empty, keeping payloads clean.

All methods can be chained freely:

```php
Metric::numeric('tickets', 42, 'sales')
    ->tenant('Brouwerij')
    ->meta(['label' => '42 tickets', 'color' => '#22c55e'])
    ->track();
```

### Response format

The endpoint returns JSON:

```json
{
    "app": "My Application",
    "timestamp": "2026-04-02T12:00:00+00:00",
    "metrics": [
        {
            "name": "active_users",
            "value": 42,
            "type": "numeric",
            "group": "users",
            "suffix": null,
            "tracked": false,
            "tenant": null
        },
        {
            "name": "scanned_today",
            "value": 100,
            "type": "numeric",
            "group": "sales",
            "suffix": null,
            "tracked": false,
            "tenant": null,
            "meta": {
                "label": "100 gescand",
                "description": "+20 ten opzichte van gisteren"
            }
        }
    ]
}
```

> **Note:** The `meta` key is only present when metadata has been attached to the metric.

## Authentication

Every request to the metrics endpoint must include three headers:

| Header | Description |
|---|---|
| `X-App-Metrics-Signature` | HMAC-SHA256 signature of `{timestamp}:{nonce}` |
| `X-App-Metrics-Timestamp` | Unix timestamp (must be within 60 seconds) |
| `X-App-Metrics-Nonce` | Random string to prevent replay attacks |

### Example request (PHP)

```php
$secret = 'your-shared-secret';
$timestamp = (string) time();
$nonce = bin2hex(random_bytes(16));
$signature = hash_hmac('sha256', $timestamp . ':' . $nonce, $secret);

$response = Http::withHeaders([
    'X-App-Metrics-Signature' => $signature,
    'X-App-Metrics-Timestamp' => $timestamp,
    'X-App-Metrics-Nonce' => $nonce,
])->get('https://your-app.com/api/metrics');
```

### Example request (cURL)

```bash
TIMESTAMP=$(date +%s)
NONCE=$(openssl rand -hex 16)
SIGNATURE=$(echo -n "${TIMESTAMP}:${NONCE}" | openssl dgst -sha256 -hmac "your-shared-secret" | awk '{print $2}')

curl -s https://your-app.com/api/metrics \
  -H "X-App-Metrics-Signature: ${SIGNATURE}" \
  -H "X-App-Metrics-Timestamp: ${TIMESTAMP}" \
  -H "X-App-Metrics-Nonce: ${NONCE}"
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
