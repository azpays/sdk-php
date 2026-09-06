# AzPays PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azpays/sdk-php.svg)](https://packagist.org/packages/azpays/sdk-php)
[![Total Downloads](https://img.shields.io/packagist/dt/azpays/sdk-php.svg)](https://packagist.org/packages/azpays/sdk-php)
[![License](https://img.shields.io/packagist/l/azpays/sdk-php.svg)](https://github.com/azpays/sdk-php/blob/main/LICENSE)

The official, merchant-focused PHP SDK for the [AzPays](https://azpays.net) crypto payment platform.

## Requirements

- **PHP 8.1** or higher
- `json` and `curl` extensions enabled

## Installation

Install the package via [Composer](https://getcomposer.org/):

```bash
composer require azpays/sdk-php
```

## API Reference & Interactive Documentation

If you need to inspect raw endpoints, request/response schemas, or test requests interactively in your browser, check out our OpenAPI portals:

* **Interactive Scalar Documentation:** [https://api.azpays.net/docs/scalar](https://api.azpays.net/docs/scalar) — Modern API reference with live code generation and testing.
* **Swagger UI Explorer:** [https://api.azpays.net/docs](https://api.azpays.net/docs) — Classic OpenAPI interactive documentation.
* **Raw OpenAPI 3.0.3 Specification:** [https://api.azpays.net/openapi.json](https://api.azpays.net/openapi.json) — Complete machine-readable schema for code generators, Postman, or Insomnia.

---

## Quick Start

```php
use AzPays\Client;

require_once __DIR__ . '/vendor/autoload.php';

$client = new Client('az_live_your_api_key');

// Create a payment
$payment = $client->payments->create([
    'fiat_amount' => 29.99,
    'description' => 'Order #1234',
]);

echo "Payment created: {$payment['id']} (token: {$payment['token']})\n";
```

---

## Configuration

```php
use AzPays\Client;

// Production (default)
$client = new Client('az_live_...');

// Sandbox / Local development
$client = new Client('az_test_...', [
    'base_url' => 'http://localhost:8080',
]);

// With all custom options
$client = new Client('az_live_...', [
    'base_url'    => 'https://api.azpays.net',
    'timeout'     => 15,            // HTTP client timeout in seconds (default: 30)
    'max_retries' => 3,             // Exponential backoff retries on 429 or 5xx (default: 3)
    'debug'       => false,         // Log requests and responses to error_log
]);
```

---

## Services Overview

| Service | Description |
|:---|:---|
| `$client->payments` | Create, retrieve, list, and view statistics for crypto payments |
| `$client->checkout` | Public checkout sessions, coin selection, rate locking, and discounts |
| `$client->invoices` | Full invoice lifecycle (draft → items → finalize → send → void) |
| `$client->paymentLinks` | Reusable hosted payment walls and shortlinks |
| `$client->webhooks` | Delivery logs + HMAC-SHA256 signature verification |
| `$client->wallets` | Wallet address generation, balance queries, fee estimation, and HD derivation |
| `$client->prices` | Real-time crypto price quotes and 24h candlesticks |
| `$client->payouts` | Merchant settlement disbursements and reporting |
| `$client->merchants` | Merchant profile self-management, key rotation, and dynamic multi-chain asset catalog |

---

## Usage Examples

### Payments

```php
// Create a payment
$payment = $client->payments->create([
    'fiat_amount'     => 49.99,
    'description'     => 'Premium Plan Subscription',
    'accepted_chains' => ['trx', 'bnb', 'ton'],
    'accepted_tokens' => ['USDT'],
]);

// Retrieve a payment by ID or token
$payment = $client->payments->get('payment-id-or-token');

// List payments with filters
$response = $client->payments->list([
    'page'      => 1,
    'per_page'  => 20,
    'statuses'  => [AzPays\Constants::PAYMENT_STATUS_CONFIRMED],
    'date_from' => '2024-01-01',
]);

$payments = $response['data'];
$pagination = $response['pagination'];

// Get payment statistics
$stats = $client->payments->stats();
echo "Success rate: {$stats['success_rate']}%\n";
```

### Checkout Sessions

```php
// Get checkout session details
$session = $client->checkout->getSession('payment-token');

// List available coins for this checkout
$coins = $client->checkout->listCoins('payment-token');

// Select coin and lock the exchange rate (30 min)
$session = $client->checkout->selectCoin('payment-token', [
    'chain'  => 'trx',
    'symbol' => 'USDT',
]);

echo "Send {$session['amount']} to {$session['wallet_address']}\n";

// Poll status
$status = $client->checkout->getStatus('payment-token');
if ($status['is_completed']) {
    echo "Payment confirmed!\n";
}
```

### Invoices

```php
// Create a draft invoice
$invoice = $client->invoices->create([
    'customer_name'  => 'John Doe',
    'customer_email' => 'john@example.com',
    'items' => [
        ['description' => 'Web Development', 'quantity' => 40, 'unit_price' => 150.00],
        ['description' => 'Design Review',   'quantity' => 5,  'unit_price' => 200.00],
    ],
    'tax_percent'   => 10.0,
    'payment_terms' => 'net_30',
    'auto_finalize' => true,
]);

// Finalize a draft invoice
$invoice = $client->invoices->finalize($invoice['id']);

// Send invoice email to customer
$client->invoices->send($invoice['id'], [
    'message' => 'Please find your crypto invoice attached.',
]);
```

### Payment Links

```php
// Create a reusable hosted payment link
$link = $client->paymentLinks->create([
    'title'        => 'Pro Membership',
    'amount'       => 99.99,
    'custom_slug'  => 'pro-membership',
    'redirect_url' => 'https://example.com/thank-you',
]);

echo "Share: https://pay.azpays.net/{$link['slug']}\n";
```

### Webhook Verification

AzPays webhooks are signed using HMAC-SHA256 to prevent replay attacks and tampering. The signature is sent in the `X-AzPays-Signature` header: `t={timestamp},v1={hex_digest}`.

```php
use AzPays\Webhooks\Webhook;
use AzPays\Exceptions\SignatureVerificationException;

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_AZPAYS_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret_from_dashboard';

try {
    // Validates signature and 5-minute timestamp tolerance
    $event = Webhook::constructEvent($payload, $signatureHeader, $secret);

    switch ($event->getEvent()) {
        case 'payment.confirmed':
            $payment = $event->getData();
            // Mark order as paid in your database
            break;

        case 'payment.failed':
            // Notify customer
            break;

        case 'payout.completed':
            // Settlement disbursement completed
            break;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
```

Or verify standalone as a boolean:

```php
$isValid = Webhook::verifySignature($payload, $signatureHeader, $secret);
```

### Wallets

```php
// Generate a new wallet address
$wallet = $client->wallets->generate([
    'chain'        => 'trx',
    'token_symbol' => 'USDT',
]);

// Estimate transfer fees
$fee = $client->wallets->estimateFee([
    'chain'        => 'trx',
    'token_symbol' => 'USDT',
    'amount'       => 100.0,
]);
echo "Estimated gas: {$fee['estimated_gas_usd']} USD\n";
```

### Prices

```php
// Get real-time price
$quote = $client->prices->getQuote('BTC');
echo "BTC Price: \${$quote['price']}\n";

// Get 24h candlesticks
$candles = $client->prices->getCandlesticks('ETH');
```

### Payouts

```php
// Create a settlement payout
$payout = $client->payouts->create([
    'destination_address' => 'TAddress...',
    'chain'               => 'trx',
    'token_symbol'        => 'USDT',
    'amount'              => 500.0,
]);
```

### Merchants

```php
// Retrieve merchant profile
$merchant = $client->merchants->me();
echo "Merchant: {$merchant['name']} (ID: {$merchant['id']})\n";
```

---

## Error Handling

All API errors throw typed exceptions extending `AzPays\Exceptions\ApiException`:

```php
use AzPays\Exceptions\NotFoundException;
use AzPays\Exceptions\UnauthorizedException;
use AzPays\Exceptions\RateLimitedException;
use AzPays\Exceptions\BadRequestException;
use AzPays\Exceptions\ApiException;

try {
    $payment = $client->payments->get('nonexistent-id');
} catch (NotFoundException $e) {
    echo "Payment not found\n";
} catch (UnauthorizedException $e) {
    echo "Invalid API Key\n";
} catch (RateLimitedException $e) {
    echo "Rate limited. Retry after {$e->getRetryAfter()} seconds.\n";
} catch (BadRequestException $e) {
    echo "Validation error: {$e->getMessage()}\n";
} catch (ApiException $e) {
    echo "API error {$e->getStatusCode()}: {$e->getMessage()} (Request ID: {$e->getRequestId()})\n";
}
```

---

## Supported Blockchains & Assets (Dynamic)

AzPays dynamically supports a multi-chain catalog across Bitcoin, Ethereum, BNB Smart Chain, Solana, TRON, TON, Polygon, and stablecoins (USDT, USDC).

**Do not hardcode chains, token contracts, or decimals.** Supported networks, contract addresses, derivation paths, and decimal precisions should always be queried dynamically at runtime via the Assets API:

```php
// Fetch the live dynamic catalog of supported blockchains and tokens
$networks = $client->merchants->assets();

foreach ($networks as $network) {
    echo "Blockchain: {$network['label']} ({$network['id']}) [Native: {$network['badge']}]\n";
    foreach ($network['tokens'] as $token) {
        echo "  - Token: {$token['symbol']} ({$token['standard']}) - Decimals: {$token['decimals']}\n";
    }
}
```

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Ensure all tests pass (`vendor/bin/phpunit`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

---

## Security

If you discover any security-related issues, please email [security@azpays.net](mailto:security@azpays.net) instead of using the public issue tracker. All security vulnerabilities will be promptly addressed.

---

## License

The AzPays PHP SDK is open-sourced software licensed under the [MIT license](LICENSE).
