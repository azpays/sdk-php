<?php

declare(strict_types=1);

namespace AzPays\Exceptions;

class ApiException extends AzPaysException
{
    protected int $statusCode;
    protected ?string $requestId;
    protected ?array $responseBody;

    public function __construct(
        string $message,
        int $statusCode = 0,
        ?string $requestId = null,
        ?array $responseBody = null,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->responseBody = $responseBody;

        $msg = "azpays: {$statusCode} {$message}";
        if ($requestId !== null && $requestId !== '') {
            $msg .= " (request_id: {$requestId})";
        }

        parent::__construct($msg, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isUnauthorized(): bool
    {
        return $this->statusCode === 401;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    public function isBadRequest(): bool
    {
        return $this->statusCode === 400;
    }
}
