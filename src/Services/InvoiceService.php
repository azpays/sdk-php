<?php

declare(strict_types=1);

namespace AzPays\Services;

class InvoiceService extends AbstractService
{
    /**
     * Create a new invoice.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->transport->post('/v1/invoices', $params);
    }

    /**
     * Retrieve an invoice by ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get('/v1/invoices/' . rawurlencode($id));
    }

    /**
     * List invoices with optional pagination and filters.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     status?: string,
     *     customer_email?: string
     * } $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/invoices', $params);
    }

    /**
     * Update a draft invoice.
     *
     * @param string $id
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->transport->put('/v1/invoices/' . rawurlencode($id), $params);
    }

    /**
     * Finalize a draft invoice to make it open for payment.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function finalize(string $id): array
    {
        return $this->transport->post('/v1/invoices/' . rawurlencode($id) . '/finalize');
    }

    /**
     * Send an invoice notification to the customer.
     *
     * @param string $id
     * @param array{email?: string, message?: string} $params
     * @return array<string, mixed>
     */
    public function send(string $id, array $params = []): array
    {
        return $this->transport->post('/v1/invoices/' . rawurlencode($id) . '/send', $params);
    }

    /**
     * Void an open invoice.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function void(string $id): array
    {
        return $this->transport->post('/v1/invoices/' . rawurlencode($id) . '/void');
    }

    /**
     * Mark an invoice as uncollectible.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function markUncollectible(string $id): array
    {
        return $this->transport->post('/v1/invoices/' . rawurlencode($id) . '/mark-uncollectible');
    }

    /**
     * Retrieve aggregate invoice statistics.
     *
     * @param string|null $merchantId
     * @return array<string, mixed>
     */
    public function stats(?string $merchantId = null): array
    {
        $params = [];
        if ($merchantId !== null && $merchantId !== '') {
            $params['merchant_id'] = $merchantId;
        }

        return $this->transport->get('/v1/invoices/stats', $params);
    }
}
