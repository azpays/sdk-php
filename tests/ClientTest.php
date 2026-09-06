<?php

declare(strict_types=1);

namespace AzPays\Tests;

use AzPays\Client;
use AzPays\Constants;
use AzPays\Services\CheckoutService;
use AzPays\Services\InvoiceService;
use AzPays\Services\MerchantService;
use AzPays\Services\PaymentLinkService;
use AzPays\Services\PaymentService;
use AzPays\Services\PayoutService;
use AzPays\Services\PriceService;
use AzPays\Services\WalletService;
use AzPays\Services\WebhookService;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientInitializesAllMerchantServices(): void
    {
        $client = new Client('az_live_test_api_key', [
            'base_url' => 'https://custom.azpays.net',
            'timeout' => 15,
            'max_retries' => 2,
        ]);

        $this->assertInstanceOf(PaymentService::class, $client->payments);
        $this->assertInstanceOf(CheckoutService::class, $client->checkout);
        $this->assertInstanceOf(InvoiceService::class, $client->invoices);
        $this->assertInstanceOf(PaymentLinkService::class, $client->paymentLinks);
        $this->assertInstanceOf(WebhookService::class, $client->webhooks);
        $this->assertInstanceOf(WalletService::class, $client->wallets);
        $this->assertInstanceOf(PriceService::class, $client->prices);
        $this->assertInstanceOf(PayoutService::class, $client->payouts);
        $this->assertInstanceOf(MerchantService::class, $client->merchants);

        $this->assertSame('az_live_test_api_key', $client->getConfig()->getApiKey());
        $this->assertSame('https://custom.azpays.net', $client->getConfig()->getBaseUrl());
        $this->assertSame(15, $client->getConfig()->getTimeout());
        $this->assertSame(2, $client->getConfig()->getMaxRetries());
    }

    public function testDefaultConfigValues(): void
    {
        $client = new Client('az_test_123');

        $this->assertSame(Constants::DEFAULT_BASE_URL, $client->getConfig()->getBaseUrl());
        $this->assertSame(Constants::DEFAULT_TIMEOUT, $client->getConfig()->getTimeout());
        $this->assertSame(Constants::DEFAULT_MAX_RETRIES, $client->getConfig()->getMaxRetries());
        $this->assertFalse($client->getConfig()->isDebug());
    }
}
