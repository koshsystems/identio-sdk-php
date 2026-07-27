<?php

declare(strict_types=1);

namespace Identio\Sdk\Tests;

use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Dto\SocialProviderConfig;
use Identio\Sdk\Enum\SocialProvider;
use Identio\Sdk\Webhook\SocialProviderUpdateVerifier;
use PHPUnit\Framework\TestCase;

final class SocialProviderUpdateVerifierTest extends TestCase
{
    public function testSignatureMatchesJavaSdkCanonicalPayload(): void
    {
        $config = new IdentioConfig('https://identio.example', 42, 'api-token', 'api-secret');
        $payload = new SocialProviderConfig(
            provider: SocialProvider::Google,
            enabled: true,
            clientId: 'client-id',
            clientSecret: 'client-secret',
            clientSecretConfigured: true,
            callbackUri: 'https://site.example/callback',
        );
        $canonical = 'api-token|GOOGLE|true|client-id|client-secret|https://site.example/callback';
        $signature = base64_encode(hash_hmac('sha256', $canonical, 'api-secret', true));

        self::assertTrue((new SocialProviderUpdateVerifier($config))->verify('api-token', $signature, $payload));
    }
}
