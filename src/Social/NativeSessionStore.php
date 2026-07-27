<?php

declare(strict_types=1);

namespace Identio\Sdk\Social;

use Identio\Sdk\Exception\SocialFlowException;

final class NativeSessionStore implements SocialSessionStore
{
    public function __construct(private readonly string $namespace = 'identio_sdk')
    {
    }

    public function put(string $key, array $value): void
    {
        $this->ensureSession();
        $_SESSION[$this->namespace][$key] = $value;
    }

    public function pull(string $key): ?array
    {
        $this->ensureSession();

        $value = $_SESSION[$this->namespace][$key] ?? null;
        unset($_SESSION[$this->namespace][$key]);

        return is_array($value) ? $value : null;
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new SocialFlowException('A PHP session must be started before using NativeSessionStore.');
        }
    }
}
