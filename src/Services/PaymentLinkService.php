<?php

declare(strict_types=1);

namespace AzPays\Services;

class PaymentLinkService extends AbstractService
{
    /**
     * Create a new hosted payment link.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->transport->post('/v1/payment-links', $params);
    }

    /**
     * Retrieve a payment link by ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get('/v1/payment-links/' . rawurlencode($id));
    }

    /**
     * List payment links with optional pagination.
     *
     * @param array{page?: int, per_page?: int, search?: string} $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/payment-links', $params);
    }

    /**
     * Update an existing payment link.
     *
     * @param string $id
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->transport->put('/v1/payment-links/' . rawurlencode($id), $params);
    }

    /**
     * Delete a payment link.
     *
     * @param string $id
     * @return mixed
     */
    public function delete(string $id): mixed
    {
        return $this->transport->delete('/v1/payment-links/' . rawurlencode($id));
    }
}
