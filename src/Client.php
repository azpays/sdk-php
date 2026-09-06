<?php

declare(strict_types=1);

namespace AzPays;

use AzPays\Http\Transport;
use AzPays\Services\CheckoutService;
use AzPays\Services\InvoiceService;
use AzPays\Services\MerchantService;
use AzPays\Services\PaymentLinkService;
use AzPays\Services\PaymentService;
use AzPays\Services\PayoutService;
use AzPays\Services\PriceService;
use AzPays\Services\WalletService;
use AzPays\Services\WebhookService;

/**
 * Client is the AzPays API client.
 *
 * Usage:
 *   $client = new \AzPays\Client('az_live_your_api_key');
 *   $payment = $client->payments->create([
 *       'fiat_amount' => 29.99,
 *       'description' => 'Order #1234',
 *   ]);
 */
class Client
{
    public readonly PaymentService $payments;
    public readonly CheckoutService $checkout;
    public readonly InvoiceService $invoices;
    public readonly PaymentLinkService $paymentLinks;
    public readonly WebhookService $webhooks;
    public readonly WalletService $wallets;
    public readonly PriceService $prices;
    public readonly PayoutService $payouts;
    public readonly MerchantService $merchants;

    private Config $config;
    private Transport $transport;

    /**
     * @param string|Config $apiKeyOrConfig API Key string or pre-built Config object
     * @param array<string, mixed> $options Optional config options if string API key is passed
     */
    public function __construct(string|Config $apiKeyOrConfig, array $options = [])
    {
        if ($apiKeyOrConfig instanceof Config) {
            $this->config = $apiKeyOrConfig;
        } else {
            $this->config = Config::fromArray($apiKeyOrConfig, $options);
        }

        $this->transport = new Transport($this->config);

        // Merchant-scoped services
        $this->payments = new PaymentService($this->transport);
        $this->checkout = new CheckoutService($this->transport);
        $this->invoices = new InvoiceService($this->transport);
        $this->paymentLinks = new PaymentLinkService($this->transport);
        $this->webhooks = new WebhookService($this->transport);
        $this->wallets = new WalletService($this->transport);
        $this->prices = new PriceService($this->transport);
        $this->payouts = new PayoutService($this->transport);
        $this->merchants = new MerchantService($this->transport);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getTransport(): Transport
    {
        return $this->transport;
    }
}
