<?php

declare(strict_types=1);

namespace Identio\Sdk\Social;

use Identio\Sdk\Dto\AuthResult;
use Identio\Sdk\Dto\ProfileValue;
use Identio\Sdk\Dto\SocialStart;
use Identio\Sdk\Enum\Language;
use Identio\Sdk\Enum\SocialProvider;
use Identio\Sdk\Exception\SocialFlowException;

final readonly class SocialFlowManager
{
    private const SESSION_KEY = 'pending_social_auth';

    public function __construct(
        private SocialAuthClient $client,
        private SocialSessionStore $session,
        private int $ttlSeconds = 600,
    ) {
        if ($this->ttlSeconds <= 0) {
            throw new SocialFlowException('Social flow TTL must be greater than zero.');
        }
    }

    /**
     * @param list<ProfileValue> $values
     */
    public function start(
        SocialProvider $provider,
        array $values = [],
        Language $language = Language::English,
    ): SocialStart {
        $response = $this->client->start($provider);

        if ($response->authorizeUrl === '') {
            throw new SocialFlowException('Identio did not return a social authorization URL.');
        }

        if ($response->state === null) {
            throw new SocialFlowException('Identio did not return social authorization state.');
        }

        if ($provider === SocialProvider::Vk && $response->vkCodeVerifier === null) {
            throw new SocialFlowException('Identio did not return a VK PKCE code verifier.');
        }

        $this->session->put(self::SESSION_KEY, [
            'provider' => $provider->value,
            'language' => $language->value,
            'state' => $response->state,
            'vkCodeVerifier' => $response->vkCodeVerifier,
            'values' => array_map(
                static fn (ProfileValue $value): array => $value->toRequestArray(),
                $values,
            ),
            'expiresAt' => time() + $this->ttlSeconds,
        ]);

        return $response;
    }

    public function complete(
        SocialProvider $provider,
        ?string $code,
        ?string $state,
        ?string $vkDeviceId = null,
        ?string $providerError = null,
    ): AuthResult {
        $pending = $this->session->pull(self::SESSION_KEY);

        if ($pending === null) {
            throw new SocialFlowException('Pending Identio social authentication session was not found.');
        }

        if (($pending['provider'] ?? null) !== $provider->value) {
            throw new SocialFlowException('Social provider does not match the pending Identio session.');
        }

        if ((int) ($pending['expiresAt'] ?? 0) <= time()) {
            throw new SocialFlowException('Pending Identio social authentication session has expired.');
        }

        $state = $this->nullableString($state);
        if ($state === null) {
            throw new SocialFlowException('Social authorization state is missing.');
        }

        if (! hash_equals((string) ($pending['state'] ?? ''), $state)) {
            throw new SocialFlowException('Social authorization state does not match.');
        }

        if ($this->nullableString($providerError) !== null) {
            throw new SocialFlowException('The social provider rejected authorization.');
        }

        $code = $this->nullableString($code);
        if ($code === null) {
            throw new SocialFlowException('Social authorization code is missing.');
        }

        $values = [];
        foreach (($pending['values'] ?? []) as $value) {
            if (is_array($value)) {
                $values[] = ProfileValue::fromArray($value);
            }
        }

        return $this->client->authenticate(
            provider: $provider,
            code: $code,
            state: $state,
            vkCodeVerifier: $this->nullableString($pending['vkCodeVerifier'] ?? null),
            vkDeviceId: $vkDeviceId,
            values: $values,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
