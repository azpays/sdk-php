<?php

declare(strict_types=1);

namespace AzPays\Services;

class MerchantService extends AbstractService
{
    /**
     * Retrieve the current authenticated merchant associated with the client's API key.
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->get('me');
    }

    /**
     * Retrieve a merchant by ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get('/v1/merchants/' . rawurlencode($id));
    }

    /**
     * Create a new merchant.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->transport->post('/v1/merchants', $params);
    }

    /**
     * List merchants with optional pagination.
     *
     * @param array{page?: int, per_page?: int, search?: string} $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/merchants', $params);
    }

    /**
     * Update an existing merchant.
     *
     * @param string $id
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->transport->put('/v1/merchants/' . rawurlencode($id), $params);
    }

    /**
     * Regenerate the API key for a merchant.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function regenerateApiKey(string $id): array
    {
        return $this->transport->post('/v1/merchants/' . rawurlencode($id) . '/regenerate-key');
    }

    /**
     * Retrieve the live dynamic catalog of supported blockchain networks, native coins, and tokens.
     * Always query this endpoint rather than hardcoding chain or token parameters.
     *
     * @return array<mixed>
     */
    public function assets(): array
    {
        return $this->transport->get('/v1/merchants/assets');
    }
}
