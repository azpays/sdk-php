<?php

declare(strict_types=1);

namespace AzPays\Services;

class WalletService extends AbstractService
{
    /**
     * Generate a new wallet address.
     *
     * @param array{chain: string, token_symbol?: string} $params
     * @return array<string, mixed>
     */
    public function generate(array $params): array
    {
        return $this->transport->post('/wallets/generate', $params);
    }

    /**
     * Retrieve balance summary for a wallet address.
     *
     * @param array{chain: string, address: string, token_symbol?: string} $params
     * @return mixed
     */
    public function getSummary(array $params): mixed
    {
        return $this->transport->post('/wallets/summary', $params);
    }

    /**
     * Initiate a blockchain transfer.
     *
     * @param array{
     *     chain: string,
     *     from: string,
     *     to: string,
     *     amount: string,
     *     token_symbol?: string,
     *     memo?: string
     * } $params
     * @return array<string, mixed>
     */
    public function transfer(array $params): array
    {
        return $this->transport->post('/wallets/transfer', $params);
    }

    /**
     * Calculate network/gas and platform fees for a transfer.
     *
     * @param array{
     *     chain: string,
     *     token_symbol: string,
     *     amount: float,
     *     from?: string,
     *     to?: string
     * } $params
     * @return array<string, mixed>
     */
    public function estimateFee(array $params): array
    {
        return $this->transport->post('/wallets/estimate-fee', $params);
    }

    /**
     * Generate a new BIP-39 HD wallet master seed.
     *
     * @param array{label?: string, word_count?: int} $params
     * @return mixed
     */
    public function generateHD(array $params = []): mixed
    {
        return $this->transport->post('/v1/wallets/hd/generate', $params);
    }

    /**
     * Import an existing BIP-39 mnemonic phrase.
     *
     * @param array{mnemonic: string, label?: string} $params
     * @return mixed
     */
    public function importHD(array $params): mixed
    {
        return $this->transport->post('/v1/wallets/hd/import', $params);
    }

    /**
     * Derive a child wallet at a specific derivation index.
     *
     * @param array{
     *     seed_id: string,
     *     chain: string,
     *     account_index?: int,
     *     address_index?: int
     * } $params
     * @return mixed
     */
    public function deriveChild(array $params): mixed
    {
        return $this->transport->post('/v1/wallets/hd/derive', $params);
    }

    /**
     * List all wallets for the authenticated merchant.
     *
     * @param array{page?: int, per_page?: int, search?: string} $params
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     */
    public function list(array $params = []): array
    {
        return $this->transport->getPaginated('/v1/wallets', $params);
    }

    /**
     * Retrieve the balance of a specific wallet ID.
     *
     * @param string $id
     * @return array<string, mixed>
     */
    public function getBalance(string $id): array
    {
        return $this->transport->get('/v1/wallets/' . rawurlencode($id) . '/balance');
    }
}
