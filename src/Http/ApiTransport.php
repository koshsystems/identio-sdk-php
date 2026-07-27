<?php

declare(strict_types=1);

namespace Identio\Sdk\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Exception\ApiException;
use Identio\Sdk\Exception\AuthenticationException;
use Identio\Sdk\Exception\ConflictException;
use Identio\Sdk\Exception\ForbiddenException;
use Identio\Sdk\Exception\NotFoundException;
use Identio\Sdk\Exception\ServerException;
use Identio\Sdk\Exception\TransportException;
use Identio\Sdk\Exception\ValidationException;
use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ApiTransport
{
    public function __construct(
        private ClientInterface $http,
        private IdentioConfig $config,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, string> $headers
     * @return array<string, mixed>|list<mixed>|int|string|null
     */
    public function request(
        string $method,
        string $path,
        ?array $json = null,
        ?string $bearerToken = null,
        array $headers = [],
    ): array|int|string|null {
        $uri = $this->config->baseUrl . '/' . ltrim($path, '/');
        $requestHeaders = array_merge([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . ($bearerToken ?? $this->config->apiToken),
        ], $headers);

        $options = [
            'headers' => $requestHeaders,
            'timeout' => $this->config->timeoutSeconds,
            'connect_timeout' => $this->config->timeoutSeconds,
            'http_errors' => false,
        ];

        if ($json !== null) {
            $options['json'] = $json;
        }

        $this->logger->debug('Identio API request', [
            'method' => strtoupper($method),
            'path' => $path,
            'domain_id' => $this->config->domainId,
        ]);

        try {
            $response = $this->http->request($method, $uri, $options);
        } catch (GuzzleException $exception) {
            throw new TransportException('Identio API request failed: ' . $exception->getMessage(), previous: $exception);
        }

        $status = $response->getStatusCode();
        $body = trim((string) $response->getBody());
        $decoded = $this->decodeBody($body);

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) && is_string($decoded['message'] ?? null)
                ? trim($decoded['message'])
                : sprintf('Identio API returned HTTP %d.', $status);

            $this->logger->warning('Identio API error', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $status,
                'message' => $message,
            ]);

            throw $this->apiException($status, $message, is_array($decoded) ? $decoded : null);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|list<mixed>|int|string|null
     */
    private function decodeBody(string $body): array|int|string|null
    {
        if ($body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $body;
        }

        return is_array($decoded) || is_int($decoded) || is_string($decoded) || $decoded === null
            ? $decoded
            : (string) $decoded;
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function apiException(int $status, string $message, ?array $body): ApiException
    {
        return match (true) {
            $status === 401 => new AuthenticationException($message, $status, $body),
            $status === 403 => new ForbiddenException($message, $status, $body),
            $status === 404 => new NotFoundException($message, $status, $body),
            $status === 409 => new ConflictException($message, $status, $body),
            $status === 400 || $status === 422 => new ValidationException($message, $status, $body),
            $status >= 500 => new ServerException($message, $status, $body),
            default => new ApiException($message, $status, $body),
        };
    }
}
