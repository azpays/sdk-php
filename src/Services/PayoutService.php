<?php

declare(strict_types=1);

namespace AzPays\Services;

class PayoutService extends AbstractService
{
    /**
     * Create a new settlement payout disbursement request.
     *
     * @param array{
     *     destination_address: string,
     *     chain: string,
     *     network?: string,
     *     token_symbol: string,
     *     token_address?: string,
     *     amount: float,
     *     fiat_amount?: float,
     *     schedule_type?: string,
     *     memo?: string,
     *     idempotency_key?: string
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->transport->post('/v1/payouts', $params);
    }

    /**
     * Retrieve a payout by ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get('/v1/payouts/' . rawurlencode($id));
    }

    /**
     * List payouts with optional filters.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     status?: string,
     *     chain?: string
     * } $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/payouts', $params);
    }

    /**
     * Retrieve aggregate payout statistics for the authenticated merchant.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->transport->get('/v1/payouts/stats');
    }
}
