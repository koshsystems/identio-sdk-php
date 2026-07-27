<?php

declare(strict_types=1);

namespace Identio\Sdk\Social;

interface SocialSessionStore
{
    /**
     * @param array<string, mixed> $value
     */
    public function put(string $key, array $value): void;

    /**
     * Returns and removes the value.
     *
     * @return array<string, mixed>|null
     */
    public function pull(string $key): ?array;
}
