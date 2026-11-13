<?php

declare(strict_types=1);

/**
 * src/Data/Event.php
 *
 * Typed DTO for a single thing that happened in the app (a ticket sold, credits
 * deducted), for the dashboard timeline. Events are read from the app's own
 * tables on request; the package stores nothing.
 *
 * Keep the text free of personal data: the dashboard shows it as is.
 */

namespace Schmeits\AppMetrics\Data;

use DateTimeInterface;

class Event
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $id,
        public readonly DateTimeInterface $at,
        public readonly string $text,
        public ?string $type = null,
        public ?string $tenant = null,
        public array $meta = [],
    ) {}

    /**
     * Create an event. The id must be stable and unique within the app (for
     * example "ticket-order:123"), so the dashboard can store it only once.
     */
    public static function make(string $id, DateTimeInterface $at, string $text): self
    {
        return new self($id, $at, $text);
    }

    /**
     * Short machine-readable kind, such as "sale", "scan" or "usage".
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function tenant(string $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * @return array{id: string, at: string, text: string, type: ?string, tenant: ?string, meta?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'at' => $this->at->format(DateTimeInterface::ATOM),
            'text' => $this->text,
            'type' => $this->type,
            'tenant' => $this->tenant,
        ];

        if ($this->meta !== []) {
            $data['meta'] = $this->meta;
        }

        return $data;
    }
}
