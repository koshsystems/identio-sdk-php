<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

final readonly class SocialStart
{
    public function __construct(
        public string $authorizeUrl,
        public ?string $state = null,
        public ?string $vkCodeVerifier = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            authorizeUrl: trim((string) ($data['authorizeUrl'] ?? '')),
            state: self::nullableString($data['state'] ?? null),
            vkCodeVerifier: self::nullableString($data['vkCodeVerifier'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
