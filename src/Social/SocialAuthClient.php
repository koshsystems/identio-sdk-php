<?php

declare(strict_types=1);

namespace Identio\Sdk\Social;

use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Dto\AuthResult;
use Identio\Sdk\Dto\ProfileValue;
use Identio\Sdk\Dto\SocialProviderConfig;
use Identio\Sdk\Dto\SocialStart;
use Identio\Sdk\Enum\SocialProvider;
use Identio\Sdk\Http\ApiTransport;
use UnexpectedValueException;

final readonly class SocialAuthClient
{
    public function __construct(
        private ApiTransport $transport,
        private IdentioConfig $config,
    ) {
    }

    public function start(SocialProvider $provider): SocialStart
    {
        $response = $this->transport->request(
            'GET',
            sprintf('%s/social/%s/start', $this->basePath(), $provider->value),
        );

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio social start response must be a JSON object.');
        }

        return SocialStart::fromArray($response);
    }

    /**
     * @param list<ProfileValue> $values
     */
    public function authenticate(
        SocialProvider $provider,
        string $code,
        string $state,
        ?string $vkCodeVerifier = null,
        ?string $vkDeviceId = null,
        array $values = [],
    ): AuthResult {
        return $this->authenticateRequest($provider, [
            'code' => trim($code),
            'vkCodeVerifier' => $this->nullableString($vkCodeVerifier),
            'vkDeviceId' => $this->nullableString($vkDeviceId),
            'state' => trim($state),
            'registrationToken' => null,
            'values' => $this->profileValues($values),
        ]);
    }

    /**
     * @param list<ProfileValue> $values
     */
    public function completeRegistration(
        SocialProvider $provider,
        string $registrationToken,
        array $values,
    ): AuthResult {
        return $this->authenticateRequest($provider, [
            'code' => null,
            'vkCodeVerifier' => null,
            'vkDeviceId' => null,
            'state' => null,
            'registrationToken' => trim($registrationToken),
            'values' => $this->profileValues($values),
        ]);
    }

    public function config(SocialProvider $provider): SocialProviderConfig
    {
        $response = $this->transport->request(
            'GET',
            sprintf('%s/social/%s/config', $this->basePath(), $provider->value),
        );

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio social provider response must be a JSON object.');
        }

        return SocialProviderConfig::fromArray($response);
    }

    /**
     * @return list<SocialProviderConfig>
     */
    public function configs(?string $clientIp = null): array
    {
        $headers = [];
        if ($this->nullableString($clientIp) !== null) {
            $headers['X-Userid-Client-Ip'] = trim((string) $clientIp);
        }

        $response = $this->transport->request(
            'GET',
            $this->basePath() . '/social/configs',
            headers: $headers,
        );

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio social providers response must be a JSON array.');
        }

        $configs = [];
        foreach ($response as $item) {
            if (is_array($item)) {
                $configs[] = SocialProviderConfig::fromArray($item);
            }
        }

        return $configs;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function authenticateRequest(SocialProvider $provider, array $payload): AuthResult
    {
        $response = $this->transport->request(
            'POST',
            sprintf('%s/social-login/%s', $this->basePath(), $provider->value),
            $payload,
        );

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio social authentication response must be a JSON object.');
        }

        return AuthResult::fromArray($response);
    }

    private function basePath(): string
    {
        return $this->config->domainUsersPath();
    }

    /**
     * @param list<ProfileValue> $values
     * @return list<array<string, mixed>>
     */
    private function profileValues(array $values): array
    {
        return array_values(array_map(
            static fn (ProfileValue $value): array => $value->toRequestArray(),
            $values,
        ));
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
