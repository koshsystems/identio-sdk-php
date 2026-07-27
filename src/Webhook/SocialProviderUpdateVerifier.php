<?php

declare(strict_types=1);

namespace Identio\Sdk\Webhook;

use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Dto\SocialProviderConfig;
use Identio\Sdk\Exception\ConfigurationException;

final readonly class SocialProviderUpdateVerifier
{
    public function __construct(private IdentioConfig $config)
    {
    }

    public function verify(
        string $apiTokenHeader,
        string $signatureHeader,
        SocialProviderConfig $payload,
    ): bool {
        if ($this->config->apiSecret === null) {
            throw new ConfigurationException('Identio API secret is required to verify social provider updates.');
        }

        if (! hash_equals($this->config->apiToken, trim($apiTokenHeader))) {
            return false;
        }

        $canonical = implode('|', [
            $this->config->apiToken,
            $payload->provider->value,
            $payload->enabled ? 'true' : 'false',
            $payload->clientId ?? '',
            $payload->clientSecret ?? '',
            $payload->callbackUri ?? '',
        ]);

        $expected = base64_encode(hash_hmac('sha256', $canonical, $this->config->apiSecret, true));

        return hash_equals($expected, trim($signatureHeader));
    }
}
