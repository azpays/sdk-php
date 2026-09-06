<?php

declare(strict_types=1);

namespace AzPays\Services;

class PaymentService extends AbstractService
{
    /**
     * Create a new payment request.
     *
     * @param array{
     *     fiat_amount: float,
     *     description?: string,
     *     idempotency_key?: string,
     *     accepted_chains?: array<string>,
     *     accepted_tokens?: array<string>
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->transport->post('/v1/payments', $params);
    }

    /**
     * Retrieve a payment by ID or token.
     *
     * @param string $idOrToken
     * @return array<string, mixed>
     */
    public function get(string $idOrToken): array
    {
        return $this->transport->get('/v1/payments/' . rawurlencode($idOrToken));
    }

    /**
     * List payments with optional filters.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     statuses?: array<int>|string,
     *     date_from?: string,
     *     date_to?: string
     * } $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/payments', $params);
    }

    /**
     * Retrieve aggregate payment statistics.
     *
     * @param array{
     *     date_from?: string,
     *     date_to?: string
     * } $params
     * @return array<string, mixed>
     */
    public function stats(array $params = []): array
    {
        return $this->transport->get('/v1/payments/stats', $params);
    }

    /**
     * Retrieve payment reporting metrics.
     *
     * @param array{
     *     date_from?: string,
     *     date_to?: string
     * } $params
     * @return array<string, mixed>
     */
    public function reports(array $params = []): array
    {
        return $this->transport->get('/v1/payments/reports', $params);
    }
}
