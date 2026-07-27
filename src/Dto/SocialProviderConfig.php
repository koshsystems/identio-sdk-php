<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

use Identio\Sdk\Enum\SocialProvider;

final readonly class SocialProviderConfig
{
    public function __construct(
        public SocialProvider $provider,
        public bool $enabled,
        public ?string $clientId,
        public ?string $clientSecret,
        public bool $clientSecretConfigured,
        public ?string $callbackUri,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: SocialProvider::from(strtoupper((string) ($data['provider'] ?? ''))),
            enabled: (bool) ($data['enabled'] ?? false),
            clientId: self::nullableString($data['clientId'] ?? null),
            clientSecret: self::nullableString($data['clientSecret'] ?? null),
            clientSecretConfigured: (bool) ($data['clientSecretConfigured'] ?? false),
            callbackUri: self::nullableString($data['callbackUri'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'enabled' => $this->enabled,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'clientSecretConfigured' => $this->clientSecretConfigured,
            'callbackUri' => $this->callbackUri,
        ];
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
