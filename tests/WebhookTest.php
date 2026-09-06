<?php

declare(strict_types=1);

namespace AzPays\Tests;

use AzPays\Exceptions\SignatureVerificationException;
use AzPays\Webhooks\Webhook;
use AzPays\Webhooks\WebhookEvent;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private string $secret = 'whsec_test_secret_12345';
    private string $payload = '{"event":"payment.confirmed","timestamp":1700000000,"data":{"id":"pay_123","status":4}}';

    public function testValidSignatureVerification(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue(Webhook::verifySignature($this->payload, $header, $this->secret));
    }

    public function testTamperedPayloadFailsVerification(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $tamperedPayload = '{"event":"payment.confirmed","data":{"amount":999999}}';
        $this->assertFalse(Webhook::verifySignature($tamperedPayload, $header, $this->secret));
    }

    public function testTamperedSecretFailsVerification(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", 'wrong_secret');
        $header = "t={$timestamp},v1={$signature}";

        $this->assertFalse(Webhook::verifySignature($this->payload, $header, $this->secret));
    }

    public function testExpiredTimestampFailsVerification(): void
    {
        // 10 minutes ago, tolerance is 300 seconds (5 min)
        $timestamp = time() - 600;
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertFalse(Webhook::verifySignature($this->payload, $header, $this->secret, 300));
    }

    public function testMalformedHeaderReturnsFalse(): void
    {
        $this->assertFalse(Webhook::verifySignature($this->payload, 'invalid-header', $this->secret));
        $this->assertFalse(Webhook::verifySignature($this->payload, '', $this->secret));
        $this->assertFalse(Webhook::verifySignature($this->payload, 't=123', $this->secret));
        $this->assertFalse(Webhook::verifySignature($this->payload, 'v1=abc', $this->secret));
    }

    public function testConstructEventSuccess(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $event = Webhook::constructEvent($this->payload, $header, $this->secret);
        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame('payment.confirmed', $event->getEvent());
        $this->assertSame('pay_123', $event->getData()['id']);
    }

    public function testConstructEventThrowsOnInvalidSignature(): void
    {
        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($this->payload, 't=1,v1=invalid', $this->secret);
    }
}
