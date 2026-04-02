<?php

declare(strict_types=1);

/**
 * src/Data/Metric.php
 *
 * Typed DTO representing a single business metric.
 * Uses static factory methods following the SidebarItem pattern from spatie/laravel-there-there.
 */

namespace Schmeits\AppMetrics\Data;

class Metric
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        public readonly MetricType $type,
        public readonly ?string $group = null,
        public readonly ?string $suffix = null,
        public bool $tracked = false,
        public ?string $tenant = null,
        public array $meta = [],
    ) {}

    /**
     * Create a numeric metric (counts, quantities).
     */
    public static function numeric(string $name, int|float $value, ?string $group = null): self
    {
        return new self($name, $value, MetricType::Numeric, $group);
    }

    /**
     * Create a currency metric (money amounts).
     */
    public static function currency(string $name, float $value, ?string $group = null, string $suffix = 'EUR'): self
    {
        return new self($name, $value, MetricType::Currency, $group, $suffix);
    }

    /**
     * Create a percentage metric.
     */
    public static function percentage(string $name, float $value, ?string $group = null): self
    {
        return new self($name, $value, MetricType::Percentage, $group, '%');
    }

    /**
     * Create a string metric (labels, statuses).
     */
    public static function string(string $name, string $value, ?string $group = null): self
    {
        return new self($name, $value, MetricType::String, $group);
    }

    /**
     * Mark this metric for daily snapshot tracking on the dashboard.
     */
    public function track(): self
    {
        $this->tracked = true;

        return $this;
    }

    /**
     * Set the tenant this metric belongs to (for multi-tenant apps).
     */
    public function tenant(string $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Attach arbitrary display metadata (label, description, color, etc.).
     *
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Serialize to array for JSON response.
     *
     * @return array{name: string, value: mixed, type: string, group: ?string, suffix: ?string, tracked: bool, tenant: ?string, meta?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type->value,
            'group' => $this->group,
            'suffix' => $this->suffix,
            'tracked' => $this->tracked,
            'tenant' => $this->tenant,
        ];

        if ($this->meta !== []) {
            $data['meta'] = $this->meta;
        }

        return $data;
    }
}
