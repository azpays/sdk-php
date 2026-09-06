<?php

declare(strict_types=1);

namespace AzPays;

final class Constants
{
    public const VERSION = '1.0.0';
    public const DEFAULT_BASE_URL = 'https://api.azpays.net';
    public const DEFAULT_TIMEOUT = 30; // seconds
    public const DEFAULT_MAX_RETRIES = 3;
    public const DEFAULT_USER_AGENT = 'azpays-sdk-php/' . self::VERSION;

    // Payment Statuses (matching API state machine)
    public const PAYMENT_STATUS_REGISTERED = 0;
    public const PAYMENT_STATUS_STARTED = 1;
    public const PAYMENT_STATUS_DETECTED = 2;
    public const PAYMENT_STATUS_VERIFYING = 3;
    public const PAYMENT_STATUS_CONFIRMED = 4;
    public const PAYMENT_STATUS_CANCELED = 5;
    public const PAYMENT_STATUS_PARTIAL = 6;
    public const PAYMENT_STATUS_PAID_OUT = 7;

    // Invoice Statuses
    public const INVOICE_STATUS_DRAFT = 'draft';
    public const INVOICE_STATUS_OPEN = 'open';
    public const INVOICE_STATUS_PAID = 'paid';
    public const INVOICE_STATUS_VOID = 'void';
    public const INVOICE_STATUS_UNCOLLECTIBLE = 'uncollectible';
    public const INVOICE_STATUS_EXPIRED = 'expired';

    // Payout Statuses
    public const PAYOUT_STATUS_DRAFT = 'draft';
    public const PAYOUT_STATUS_PENDING_APPROVAL = 'pending_approval';
    public const PAYOUT_STATUS_APPROVED = 'approved';
    public const PAYOUT_STATUS_PROCESSING = 'processing';
    public const PAYOUT_STATUS_COMPLETED = 'completed';
    public const PAYOUT_STATUS_REJECTED = 'rejected';
    public const PAYOUT_STATUS_CANCELLED = 'cancelled';
    public const PAYOUT_STATUS_FAILED = 'failed';

    // Webhook Statuses
    public const WEBHOOK_STATUS_PENDING = 'pending';
    public const WEBHOOK_STATUS_DELIVERED = 'delivered';
    public const WEBHOOK_STATUS_FAILED = 'failed';
}
