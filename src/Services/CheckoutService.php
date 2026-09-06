<?php

declare(strict_types=1);

namespace AzPays\Services;

class CheckoutService extends AbstractService
{
    /**
     * Retrieve checkout session details by payment token.
     *
     * @param string $token
     * @return array<string, mixed>
     */
    public function getSession(string $token): array
    {
        return $this->transport->get('/v1/checkout/' . rawurlencode($token));
    }

    /**
     * List available coins/assets for a checkout session.
     *
     * @param string $token
     * @return array<mixed>
     */
    public function listCoins(string $token): array
    {
        return $this->transport->get('/v1/checkout/' . rawurlencode($token) . '/coins');
    }

    /**
     * Select blockchain and token for payment, locking the rate and returning deposit address.
     *
     * @param string $token
     * @param array{chain: string, symbol: string} $params
     * @return array<string, mixed>
     */
    public function selectCoin(string $token, array $params): array
    {
        return $this->transport->post('/v1/checkout/' . rawurlencode($token) . '/select-coin', $params);
    }

    /**
     * Poll the current status of a checkout session.
     *
     * @param string $token
     * @return array<string, mixed>
     */
    public function getStatus(string $token): array
    {
        return $this->transport->get('/v1/checkout/' . rawurlencode($token) . '/status');
    }

    /**
     * Apply a discount code to a checkout session.
     *
     * @param string $token
     * @param array{code: string, payer_email?: string} $params
     * @return array<string, mixed>
     */
    public function applyDiscount(string $token, array $params): array
    {
        return $this->transport->post('/v1/checkout/' . rawurlencode($token) . '/discount', $params);
    }

    /**
     * Remove a previously applied discount from a checkout session.
     *
     * @param string $token
     * @return mixed
     */
    public function removeDiscount(string $token): mixed
    {
        return $this->transport->delete('/v1/checkout/' . rawurlencode($token) . '/discount');
    }
}
