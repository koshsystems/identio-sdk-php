<?php

declare(strict_types=1);

namespace Identio\Sdk\Http;

final readonly class ApiResponse
{
    /**
     * @param array<string, mixed>|list<mixed>|int|string|null $body
     */
    public function __construct(
        public string $method,
        public string $path,
        public int $statusCode,
        public array|int|string|null $body,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function message(): ?string
    {
        if (! is_array($this->body) || ! is_string($this->body['message'] ?? null)) {
            return null;
        }

        $message = trim($this->body['message']);

        return $message === '' ? null : $message;
    }

    public function errorMessage(): string
    {
        return $this->message() ?? sprintf('Identio API returned HTTP %d.', $this->statusCode);
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    public function responseBody(): ?array
    {
        return is_array($this->body) ? $this->body : null;
    }
}
