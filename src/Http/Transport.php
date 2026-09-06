<?php

declare(strict_types=1);

namespace AzPays\Http;

use AzPays\Config;
use AzPays\Exceptions\ApiException;
use AzPays\Exceptions\BadRequestException;
use AzPays\Exceptions\ForbiddenException;
use AzPays\Exceptions\NotFoundException;
use AzPays\Exceptions\RateLimitedException;
use AzPays\Exceptions\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Transport
{
    private Config $config;
    private ClientInterface $client;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->client = $config->getHttpClient() ?? new Client([
            'timeout' => $config->getTimeout(),
            'http_errors' => false, // We handle status codes manually
        ]);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $queryParams
     * @return mixed
     * @throws ApiException
     */
    public function get(string $path, array $queryParams = []): mixed
    {
        return $this->request('GET', $path, [
            'query' => $this->filterQueryParams($queryParams),
        ]);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $queryParams
     * @return array{data: array<mixed>, pagination: array<string, mixed>}
     * @throws ApiException
     */
    public function getPaginated(string $path, array $queryParams = []): array
    {
        $res = $this->request('GET', $path, [
            'query' => $this->filterQueryParams($queryParams),
        ], true);

        if (is_array($res) && isset($res['pagination'])) {
            return [
                'data' => $res['data'] ?? [],
                'pagination' => $res['pagination'],
            ];
        }

        return [
            'data' => is_array($res) ? ($res['data'] ?? $res) : [],
            'pagination' => [],
        ];
    }

    /**
     * @param string $path
     * @param mixed $body
     * @return mixed
     * @throws ApiException
     */
    public function post(string $path, mixed $body = null): mixed
    {
        $options = [];
        if ($body !== null) {
            $options['json'] = $body;
        }

        return $this->request('POST', $path, $options);
    }

    /**
     * @param string $path
     * @param mixed $body
     * @return mixed
     * @throws ApiException
     */
    public function put(string $path, mixed $body = null): mixed
    {
        $options = [];
        if ($body !== null) {
            $options['json'] = $body;
        }

        return $this->request('PUT', $path, $options);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $queryParams
     * @return mixed
     * @throws ApiException
     */
    public function delete(string $path, array $queryParams = []): mixed
    {
        return $this->request('DELETE', $path, [
            'query' => $this->filterQueryParams($queryParams),
        ]);
    }

    /**
     * Executes HTTP request with exponential backoff and retries on 429 or 5xx.
     *
     * @param string $method
     * @param string $path
     * @param array<string, mixed> $options
     * @param bool $isPaginated
     * @return mixed
     * @throws ApiException
     */
    private function request(string $method, string $path, array $options = [], bool $isPaginated = false): mixed
    {
        $url = rtrim($this->config->getBaseUrl(), '/') . '/' . ltrim($path, '/');

        $headers = [
            'X-API-Key' => $this->config->getApiKey(),
            'User-Agent' => $this->config->getUserAgent(),
            'Accept' => 'application/json',
        ];

        if (isset($options['json'])) {
            $headers['Content-Type'] = 'application/json';
        }

        $options['headers'] = array_merge($headers, $options['headers'] ?? []);
        $options['http_errors'] = false; // Always prevent Guzzle from throwing on 4xx/5xx

        $maxRetries = $this->config->getMaxRetries();
        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 0) {
                // Exponential backoff: 500ms, 1000ms, 2000ms...
                $backoffUs = (int) ((2 ** ($attempt - 1)) * 500000);
                usleep($backoffUs);
            }

            if ($this->config->isDebug()) {
                error_log(sprintf('[azpays] %s %s (attempt %d/%d)', $method, $url, $attempt + 1, $maxRetries + 1));
                if (isset($options['json'])) {
                    error_log('[azpays] Request Body: ' . json_encode($options['json']));
                }
            }

            try {
                /** @var ResponseInterface $response */
                $response = $this->client->request($method, $url, $options);
            } catch (GuzzleException $e) {
                if (method_exists($e, 'getResponse') && $e->getResponse() instanceof ResponseInterface) {
                    $response = $e->getResponse();
                } else {
                    $lastException = new ApiException(
                        'Request failed: ' . $e->getMessage(),
                        0,
                        null,
                        null,
                        $e
                    );
                    continue;
                }
            }

            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();
            $requestId = $response->getHeaderLine('X-Request-Id') ?: null;

            if ($this->config->isDebug()) {
                error_log(sprintf('[azpays] Response %d: %s', $statusCode, substr($rawBody, 0, 500)));
            }

            $decoded = json_decode($rawBody, true);
            $parsedBody = is_array($decoded) ? $decoded : null;

            // Handle 429 Too Many Requests
            if ($statusCode === 429) {
                $retryAfterHeader = $response->getHeaderLine('Retry-After');
                $retryAfterSec = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;

                $message = $this->extractErrorMessage($parsedBody, $rawBody);
                $lastException = new RateLimitedException($message, 429, $requestId, $parsedBody, $retryAfterSec);

                if ($attempt < $maxRetries) {
                    if ($retryAfterSec !== null && $retryAfterSec > 0 && $retryAfterSec <= 10) {
                        sleep($retryAfterSec);
                    }
                    continue;
                }

                throw $lastException;
            }

            // Retry on 5xx server errors
            if ($statusCode >= 500) {
                $message = $this->extractErrorMessage($parsedBody, $rawBody);
                $lastException = new ApiException($message, $statusCode, $requestId, $parsedBody);
                continue;
            }

            // Client errors (4xx non-retryable)
            if ($statusCode >= 400) {
                $message = $this->extractErrorMessage($parsedBody, $rawBody);
                throw match ($statusCode) {
                    400 => new BadRequestException($message, 400, $requestId, $parsedBody),
                    401 => new UnauthorizedException($message, 401, $requestId, $parsedBody),
                    403 => new ForbiddenException($message, 403, $requestId, $parsedBody),
                    404 => new NotFoundException($message, 404, $requestId, $parsedBody),
                    default => new ApiException($message, $statusCode, $requestId, $parsedBody),
                };
            }

            // Success (2xx)
            if ($parsedBody !== null) {
                if ($isPaginated) {
                    return $parsedBody;
                }

                // Check standard envelope: {"ok": true, "data": ...}
                if (isset($parsedBody['ok']) && array_key_exists('data', $parsedBody)) {
                    return $parsedBody['data'];
                }

                return $parsedBody;
            }

            return $rawBody;
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new ApiException("Request failed after {$maxRetries} retries", 0);
    }

    private function extractErrorMessage(?array $parsedBody, string $rawBody): string
    {
        if ($parsedBody !== null) {
            if (!empty($parsedBody['msg'])) {
                return (string) $parsedBody['msg'];
            }
            if (!empty($parsedBody['message'])) {
                return (string) $parsedBody['message'];
            }
            if (!empty($parsedBody['error'])) {
                return is_string($parsedBody['error']) ? $parsedBody['error'] : json_encode($parsedBody['error']);
            }
        }

        $trimmed = trim($rawBody);
        if (strlen($trimmed) > 200) {
            return substr($trimmed, 0, 200) . '...';
        }

        return $trimmed !== '' ? $trimmed : 'Unknown error';
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private function filterQueryParams(array $params): array
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_bool($value)) {
                $filtered[$key] = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $filtered[$key] = implode(',', $value);
            } else {
                $filtered[$key] = (string) $value;
            }
        }
        return $filtered;
    }
}
