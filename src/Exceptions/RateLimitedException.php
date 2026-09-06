<?php

declare(strict_types=1);

namespace AzPays\Exceptions;

class RateLimitedException extends ApiException
{
    protected ?int $retryAfter;

    public function __construct(
        string $message,
        int $statusCode = 429,
        ?string $requestId = null,
        ?array $responseBody = null,
        ?int $retryAfter = null,
        ?\Throwable $previous = null
    ) {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, $statusCode, $requestId, $responseBody, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
