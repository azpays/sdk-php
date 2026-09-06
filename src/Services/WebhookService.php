<?php

declare(strict_types=1);

namespace AzPays\Services;

use AzPays\Webhooks\Webhook;
use AzPays\Webhooks\WebhookEvent;

class WebhookService extends AbstractService
{
    /**
     * List webhook delivery logs with optional filters.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     event?: string,
     *     status?: string
     * } $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function listDeliveries(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/webhooks/deliveries', $params);
    }

    /**
     * Retrieve a single webhook delivery log by ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function getDelivery(string $id): array
    {
        return $this->transport->get('/v1/webhooks/deliveries/' . rawurlencode($id));
    }

    /**
     * Send a test webhook event to a configured endpoint.
     *
     * @param array{url?: string, secret?: string, event?: string} $params
     * @return mixed
     */
    public function test(array $params = []): mixed
    {
        return $this->transport->post('/v1/webhooks/test', $params);
    }

    /**
     * Verify an incoming webhook signature.
     *
     * @param string $payload
     * @param string $signatureHeader
     * @param string $secret
     * @param int $tolerance
     * @return bool
     */
    public function verifySignature(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = Webhook::DEFAULT_TOLERANCE
    ): bool {
        return Webhook::verifySignature($payload, $signatureHeader, $secret, $tolerance);
    }

    /**
     * Construct a validated WebhookEvent instance from an incoming webhook request.
     *
     * @param string $payload
     * @param string $signatureHeader
     * @param string $secret
     * @param int $tolerance
     * @return WebhookEvent
     */
    public function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = Webhook::DEFAULT_TOLERANCE
    ): WebhookEvent {
        return Webhook::constructEvent($payload, $signatureHeader, $secret, $tolerance);
    }
}
