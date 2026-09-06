<?php

declare(strict_types=1);

namespace AzPays\Webhooks;

class WebhookEvent
{
    private string $event;
    private int $timestamp;
    private array $data;
    private array $raw;

    public function __construct(string $event, int $timestamp, array $data, array $raw = [])
    {
        $this->event = $event;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->raw = $raw;
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['event'] ?? ''),
            (int) ($payload['timestamp'] ?? 0),
            is_array($payload['data'] ?? null) ? $payload['data'] : [],
            $payload
        );
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'timestamp' => $this->timestamp,
            'data' => $this->data,
        ];
    }
}
