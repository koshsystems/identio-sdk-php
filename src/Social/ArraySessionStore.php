<?php

declare(strict_types=1);

namespace Identio\Sdk\Social;

final class ArraySessionStore implements SocialSessionStore
{
    /** @var array<string, array<string, mixed>> */
    private array $values = [];

    public function put(string $key, array $value): void
    {
        $this->values[$key] = $value;
    }

    public function pull(string $key): ?array
    {
        $value = $this->values[$key] ?? null;
        unset($this->values[$key]);

        return $value;
    }
}
