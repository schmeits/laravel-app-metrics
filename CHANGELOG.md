# Changelog

All notable changes to `laravel-app-metrics` will be documented in this file.

## [Unreleased]

### Changed
- 2026-11-16 13:11: alle dev-dependencies bijgewerkt; de testomgeving draait nu op Laravel 13 (orchestra/testbench ^10.0|^11.0). composer audit schoon, ook de 8 meldingen die nog openstonden.
- 2026-11-16 13:11: GitHub Actions (run-tests.yml): tests op PHP 8.3 en 8.4 met Laravel 12 en 13, plus PHPStan en composer audit; bij elke push en PR en elke maandag. Dependabot bundelt updates nu in een PR per week.

### Added
- 2026-11-16 13:11: test voor een onleesbare `since` op het events-endpoint (dekking nu 100%).

### Security
- 2026-11-16 13:05: dev-dependency symfony/yaml 7.4.14 naar 7.4.23 (CVE-2026-109413, onbegrensde recursie in de YAML-parser); alleen composer.lock, het package parset zelf geen YAML

## [1.2.0] - 2026-11-13

### Added
- Events for a timeline: `AppMetrics::events(fn (CarbonImmutable $since) => [...])` with the `Event` DTO (`make()`, `type()`, `tenant()`, `meta()`)
- Signed endpoint `{url}/events` with `since` (default 24 hours, at most 7 days back) and `limit` (default 200, max 500), newest first

## [1.1.0] - 2026-04-02

### Added
- `meta()` method on Metric DTO for attaching arbitrary display metadata (label, description, color, etc.)
- Meta is excluded from JSON output when empty, keeping the payload clean
- Multiple `meta()` calls merge data, allowing progressive enrichment

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
