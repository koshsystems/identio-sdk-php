<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

final readonly class AuthResult
{
    public function __construct(
        public ?string $token,
        public ?User $user,
        public bool $registrationRequired = false,
        public ?string $registrationToken = null,
        public ?string $message = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: self::nullableString($data['token'] ?? null),
            user: isset($data['user']) && is_array($data['user']) ? User::fromArray($data['user']) : null,
            registrationRequired: (bool) ($data['registrationRequired'] ?? false),
            registrationToken: self::nullableString($data['registrationToken'] ?? null),
            message: self::nullableString($data['message'] ?? null),
        );
    }

    public function isAuthenticated(): bool
    {
        return $this->token !== null && $this->user !== null && ! $this->registrationRequired;
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
