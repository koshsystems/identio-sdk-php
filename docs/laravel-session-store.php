<?php

declare(strict_types=1);

use Identio\Sdk\Social\SocialSessionStore;
use Illuminate\Contracts\Session\Session;

final readonly class LaravelIdentioSessionStore implements SocialSessionStore
{
    public function __construct(private Session $session)
    {
    }

    public function put(string $key, array $value): void
    {
        $this->session->put('identio.' . $key, $value);
    }

    public function pull(string $key): ?array
    {
        $value = $this->session->pull('identio.' . $key);

        return is_array($value) ? $value : null;
    }
}
