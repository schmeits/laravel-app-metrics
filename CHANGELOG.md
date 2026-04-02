# Changelog

All notable changes to `laravel-app-metrics` will be documented in this file.

## [Unreleased]

## [1.0.0] - 2026-04-02

### Added
- Initial release
- Metric DTO with factory methods: `numeric()`, `currency()`, `percentage()`, `string()`
- HMAC-SHA256 signature validation middleware with replay protection (60s window)
- Configurable endpoint URL and middleware via `config/app-metrics.php`
- `AppMetrics` facade for registering metric closures
- `track()` method to flag metrics for daily snapshot tracking
- `tenant()` method for multi-tenant application support
- Auto-discovery via Laravel package service provider
- Pest test suite with 21 tests, 63 assertions, 100% code coverage
- PHPStan analysis at max level (0 errors)
- Laravel Pint code formatting
