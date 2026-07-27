<?php

declare(strict_types=1);

namespace Identio\Sdk\Exception;

class ApiException extends IdentioException
{
    /**
     * @param array<string, mixed>|null $responseBody
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?array $responseBody = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
