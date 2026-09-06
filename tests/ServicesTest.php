<?php

declare(strict_types=1);

namespace AzPays\Tests;

use AzPays\Client;
use AzPays\Constants;
use AzPays\Webhooks\WebhookEvent;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ServicesTest extends TestCase
{
    /**
     * @var array<array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface}>
     */
    private array $historyContainer = [];

    private function createClientWithResponses(array $responses): Client
    {
        $this->historyContainer = [];
        $history = Middleware::history($this->historyContainer);

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        return new Client('az_live_merchant_key', [
            'http_client' => $guzzle,
            'max_retries' => 0,
        ]);
    }

    public function testPaymentService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pay_1', 'token' => 'tok_1']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pay_1']])),
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [['id' => 'pay_1']],
                'pagination' => ['total' => 1],
            ])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['success_rate' => 98.5]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['total_volume' => 125000.0]])),
        ]);

        $created = $client->payments->create(['fiat_amount' => 100.0]);
        $this->assertSame('pay_1', $created['id']);
        $this->assertSame('POST', $this->historyContainer[0]['request']->getMethod());
        $this->assertSame('/v1/payments', $this->historyContainer[0]['request']->getUri()->getPath());

        $fetched = $client->payments->get('pay_1');
        $this->assertSame('pay_1', $fetched['id']);
        $this->assertSame('GET', $this->historyContainer[1]['request']->getMethod());
        $this->assertSame('/v1/payments/pay_1', $this->historyContainer[1]['request']->getUri()->getPath());

        $list = $client->payments->list(['page' => 1, 'statuses' => [4]]);
        $this->assertCount(1, $list['data']);
        $this->assertSame('page=1&statuses=4', $this->historyContainer[2]['request']->getUri()->getQuery());

        $stats = $client->payments->stats(['date_from' => '2024-01-01']);
        $this->assertSame(98.5, $stats['success_rate']);
        $this->assertSame('date_from=2024-01-01', $this->historyContainer[3]['request']->getUri()->getQuery());

        $reports = $client->payments->reports();
        $this->assertEquals(125000.0, $reports['total_volume']);
    }

    public function testCheckoutService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['token' => 'tok_abc']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => [['symbol' => 'USDT', 'chain' => 'trx']]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['wallet_address' => 'T12345']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['is_completed' => true]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['discount_amount' => 5.0]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['discount_removed' => true]])),
        ]);

        $session = $client->checkout->getSession('tok_abc');
        $this->assertSame('tok_abc', $session['token']);

        $coins = $client->checkout->listCoins('tok_abc');
        $this->assertSame('USDT', $coins[0]['symbol']);

        $selected = $client->checkout->selectCoin('tok_abc', ['chain' => 'trx', 'symbol' => 'USDT']);
        $this->assertSame('T12345', $selected['wallet_address']);

        $status = $client->checkout->getStatus('tok_abc');
        $this->assertTrue($status['is_completed']);

        $discount = $client->checkout->applyDiscount('tok_abc', ['code' => 'SAVE5']);
        $this->assertEquals(5.0, $discount['discount_amount']);

        $client->checkout->removeDiscount('tok_abc');
        $this->assertSame('DELETE', $this->historyContainer[5]['request']->getMethod());
        $this->assertSame('/v1/checkout/tok_abc/discount', $this->historyContainer[5]['request']->getUri()->getPath());
    }

    public function testInvoiceService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'inv_1']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'inv_1']])),
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'inv_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'inv_1', 'customer_name' => 'Bob']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'inv_1', 'status' => Constants::INVOICE_STATUS_OPEN]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['sent' => true]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['status' => Constants::INVOICE_STATUS_VOID]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['status' => Constants::INVOICE_STATUS_UNCOLLECTIBLE]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['total_invoices' => 10]])),
        ]);

        $inv = $client->invoices->create(['customer_name' => 'Alice']);
        $this->assertSame('inv_1', $inv['id']);

        $fetched = $client->invoices->get('inv_1');
        $this->assertSame('inv_1', $fetched['id']);

        $list = $client->invoices->list(['status' => 'draft']);
        $this->assertCount(1, $list['data']);

        $updated = $client->invoices->update('inv_1', ['customer_name' => 'Bob']);
        $this->assertSame('Bob', $updated['customer_name']);

        $finalized = $client->invoices->finalize('inv_1');
        $this->assertSame(Constants::INVOICE_STATUS_OPEN, $finalized['status']);

        $sent = $client->invoices->send('inv_1', ['message' => 'Hello']);
        $this->assertTrue($sent['sent']);

        $voided = $client->invoices->void('inv_1');
        $this->assertSame(Constants::INVOICE_STATUS_VOID, $voided['status']);

        $uncollectible = $client->invoices->markUncollectible('inv_1');
        $this->assertSame(Constants::INVOICE_STATUS_UNCOLLECTIBLE, $uncollectible['status']);

        $stats = $client->invoices->stats('merch_1');
        $this->assertSame(10, $stats['total_invoices']);
    }

    public function testPaymentLinksService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pl_1', 'slug' => 'pro-pass']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pl_1']])),
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'pl_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pl_1', 'title' => 'Updated Pass']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['deleted' => true]])),
        ]);

        $link = $client->paymentLinks->create(['title' => 'Pro Pass']);
        $this->assertSame('pro-pass', $link['slug']);

        $get = $client->paymentLinks->get('pl_1');
        $this->assertSame('pl_1', $get['id']);

        $list = $client->paymentLinks->list(['page' => 1]);
        $this->assertCount(1, $list['data']);

        $updated = $client->paymentLinks->update('pl_1', ['title' => 'Updated Pass']);
        $this->assertSame('Updated Pass', $updated['title']);

        $client->paymentLinks->delete('pl_1');
        $this->assertSame('DELETE', $this->historyContainer[4]['request']->getMethod());
    }

    public function testWebhookService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'del_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'del_1', 'event' => 'payment.confirmed']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['success' => true]])),
        ]);

        $deliveries = $client->webhooks->listDeliveries(['event' => 'payment.confirmed']);
        $this->assertCount(1, $deliveries['data']);

        $delivery = $client->webhooks->getDelivery('del_1');
        $this->assertSame('del_1', $delivery['id']);

        $test = $client->webhooks->test(['url' => 'https://example.com/wh']);
        $this->assertTrue($test['success']);

        $secret = 'whsec_test';
        $payload = '{"event":"payment.confirmed","timestamp":1700000000,"data":{}}';
        $ts = time();
        $sig = hash_hmac('sha256', "{$ts}.{$payload}", $secret);
        $header = "t={$ts},v1={$sig}";

        $this->assertTrue($client->webhooks->verifySignature($payload, $header, $secret));
        $event = $client->webhooks->constructEvent($payload, $header, $secret);
        $this->assertInstanceOf(WebhookEvent::class, $event);
    }

    public function testWalletService(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'w_1', 'public_key' => 'T123']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['balance' => '100.5']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['tx_hash' => '0xabc', 'status' => 'success']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['total_fee' => 1.5]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['seed_id' => 's_1']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['seed_id' => 's_imported']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['address' => 'TChild1']])),
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'w_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['address' => 'T123', 'balance' => '50.0']])),
        ]);

        $gen = $client->wallets->generate(['chain' => 'trx', 'token_symbol' => 'USDT']);
        $this->assertSame('w_1', $gen['id']);

        $summary = $client->wallets->getSummary(['chain' => 'trx', 'address' => 'T123']);
        $this->assertSame('100.5', $summary['balance']);

        $transfer = $client->wallets->transfer(['chain' => 'trx', 'from' => 'T1', 'to' => 'T2', 'amount' => '10']);
        $this->assertSame('0xabc', $transfer['tx_hash']);

        $fee = $client->wallets->estimateFee(['chain' => 'trx', 'token_symbol' => 'USDT', 'amount' => 10.0]);
        $this->assertEquals(1.5, $fee['total_fee']);

        $hdGen = $client->wallets->generateHD(['label' => 'Primary']);
        $this->assertSame('s_1', $hdGen['seed_id']);

        $hdImp = $client->wallets->importHD(['mnemonic' => 'word1 word2']);
        $this->assertSame('s_imported', $hdImp['seed_id']);

        $child = $client->wallets->deriveChild(['seed_id' => 's_1', 'chain' => 'trx']);
        $this->assertSame('TChild1', $child['address']);

        $list = $client->wallets->list();
        $this->assertCount(1, $list['data']);

        $bal = $client->wallets->getBalance('w_1');
        $this->assertSame('50.0', $bal['balance']);
    }

    public function testPricesAndCandlesticks(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['symbol' => 'BTC', 'price' => 65000.0]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => [['open' => 64000.0, 'close' => 65000.0]]])),
        ]);

        $quote = $client->prices->getQuote('BTC');
        $this->assertEquals(65000.0, $quote['price']);

        $candles = $client->prices->getCandlesticks('BTC');
        $this->assertEquals(64000.0, $candles[0]['open']);
    }

    public function testPayoutsFull(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pout_1', 'amount' => 500.0]])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'pout_1']])),
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'pout_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['total_volume_usd' => 5000.0]])),
        ]);

        $payout = $client->payouts->create(['destination_address' => 'T123', 'amount' => 500.0, 'chain' => 'trx', 'token_symbol' => 'USDT']);
        $this->assertSame('pout_1', $payout['id']);

        $get = $client->payouts->get('pout_1');
        $this->assertSame('pout_1', $get['id']);

        $list = $client->payouts->list(['status' => 'completed']);
        $this->assertCount(1, $list['data']);

        $stats = $client->payouts->stats();
        $this->assertEquals(5000.0, $stats['total_volume_usd']);
    }

    public function testMerchantServiceFull(): void
    {
        $client = $this->createClientWithResponses([
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'm_me', 'name' => 'Acme Corp']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'm_1']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'm_new']])),
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 'm_1']], 'pagination' => []])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['id' => 'm_1', 'name' => 'Acme Updated']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['api_key' => 'az_live_new_key']])),
            new Response(200, [], json_encode(['ok' => true, 'data' => [
                ['id' => 'tron', 'label' => 'TRON', 'tokens' => [['symbol' => 'USDT', 'decimals' => 6]]],
            ]])),
        ]);

        $me = $client->merchants->me();
        $this->assertSame('Acme Corp', $me['name']);

        $get = $client->merchants->get('m_1');
        $this->assertSame('m_1', $get['id']);

        $created = $client->merchants->create(['name' => 'New Store', 'type' => 1]);
        $this->assertSame('m_new', $created['id']);

        $list = $client->merchants->list();
        $this->assertCount(1, $list['data']);

        $updated = $client->merchants->update('m_1', ['name' => 'Acme Updated']);
        $this->assertSame('Acme Updated', $updated['name']);

        $regen = $client->merchants->regenerateApiKey('m_1');
        $this->assertSame('az_live_new_key', $regen['api_key']);

        $assets = $client->merchants->assets();
        $this->assertSame('TRON', $assets[0]['label']);
    }
}
