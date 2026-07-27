<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

final readonly class User
{
    /**
     * @param list<ProfileValue> $values
     */
    public function __construct(
        public int $id,
        public ?string $email,
        public bool $confirmed,
        public bool $active,
        public int $domainId,
        public array $values = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $values = [];
        foreach (($data['values'] ?? []) as $value) {
            if (is_array($value)) {
                $values[] = ProfileValue::fromArray($value);
            }
        }

        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : null;

        return new self(
            id: (int) ($data['id'] ?? 0),
            email: $email === '' ? null : $email,
            confirmed: (bool) ($data['confirmed'] ?? false),
            active: (bool) ($data['active'] ?? false),
            domainId: (int) ($data['domainId'] ?? 0),
            values: $values,
        );
    }
}
