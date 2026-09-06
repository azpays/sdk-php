<?php

declare(strict_types=1);

namespace AzPays;

use GuzzleHttp\ClientInterface;

class Config
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $maxRetries;
    private bool $debug;
    private string $userAgent;
    private ?ClientInterface $httpClient;

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        int $timeout = Constants::DEFAULT_TIMEOUT,
        int $maxRetries = Constants::DEFAULT_MAX_RETRIES,
        bool $debug = false,
        ?string $userAgent = null,
        ?ClientInterface $httpClient = null
    ) {
        $this->apiKey = trim($apiKey);
        $this->baseUrl = rtrim($baseUrl ?: Constants::DEFAULT_BASE_URL, '/');
        $this->timeout = $timeout > 0 ? $timeout : Constants::DEFAULT_TIMEOUT;
        $this->maxRetries = $maxRetries >= 0 ? $maxRetries : Constants::DEFAULT_MAX_RETRIES;
        $this->debug = $debug;
        $this->userAgent = $userAgent ?: Constants::DEFAULT_USER_AGENT;
        $this->httpClient = $httpClient;
    }

    public static function fromArray(string $apiKey, array $options = []): self
    {
        return new self(
            $apiKey,
            $options['base_url'] ?? $options['baseUrl'] ?? null,
            $options['timeout'] ?? Constants::DEFAULT_TIMEOUT,
            $options['max_retries'] ?? $options['maxRetries'] ?? Constants::DEFAULT_MAX_RETRIES,
            $options['debug'] ?? false,
            $options['user_agent'] ?? $options['userAgent'] ?? null,
            $options['http_client'] ?? $options['httpClient'] ?? null
        );
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getHttpClient(): ?ClientInterface
    {
        return $this->httpClient;
    }
}
