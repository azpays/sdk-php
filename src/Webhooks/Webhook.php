<?php

declare(strict_types=1);

namespace AzPays\Webhooks;

use AzPays\Exceptions\SignatureVerificationException;

class Webhook
{
    public const DEFAULT_TOLERANCE = 300; // 5 minutes

    /**
     * Verify an AzPays webhook signature.
     *
     * Header format: t={timestamp},v1={hex_digest}
     * Signed message: {timestamp}.{payload}
     *
     * @param string $payload Raw request body
     * @param string $signatureHeader Value of X-AzPays-Signature header
     * @param string $secret Webhook secret key
     * @param int $tolerance Allowed timestamp drift in seconds (default 300)
     * @return bool
     */
    public static function verifySignature(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): bool {
        if ($signatureHeader === '' || $secret === '') {
            return false;
        }

        $parsed = self::parseSignatureHeader($signatureHeader);
        $timestamp = $parsed['t'] ?? null;
        $signature = $parsed['v1'] ?? null;

        if ($timestamp === null || $signature === null) {
            return false;
        }

        // Validate timestamp format and tolerance
        $timestampInt = (int) $timestamp;
        if ($timestampInt <= 0) {
            return false;
        }

        if ($tolerance > 0 && abs(time() - $timestampInt) > $tolerance) {
            return false;
        }

        $message = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $message, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify signature and return a typed WebhookEvent. Throws SignatureVerificationException on failure.
     *
     * @param string $payload
     * @param string $signatureHeader
     * @param string $secret
     * @param int $tolerance
     * @return WebhookEvent
     * @throws SignatureVerificationException
     */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): WebhookEvent {
        if (!self::verifySignature($payload, $signatureHeader, $secret, $tolerance)) {
            throw new SignatureVerificationException('Webhook signature verification failed or timestamp expired.');
        }

        return self::parseEvent($payload);
    }

    /**
     * Parse raw webhook payload into WebhookEvent without signature verification.
     *
     * @param string $payload
     * @return WebhookEvent
     * @throws SignatureVerificationException
     */
    public static function parseEvent(string $payload): WebhookEvent
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new SignatureVerificationException('Invalid webhook JSON payload.');
        }

        return WebhookEvent::fromArray($decoded);
    }

    /**
     * @param string $header
     * @return array<string, string>
     */
    private static function parseSignatureHeader(string $header): array
    {
        $items = [];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_starts_with($part, 't=')) {
                $items['t'] = substr($part, 2);
            } elseif (str_starts_with($part, 'v1=')) {
                $items['v1'] = substr($part, 3);
            }
        }

        return $items;
    }
}
