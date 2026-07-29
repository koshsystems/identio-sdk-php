<?php

declare(strict_types=1);

namespace Identio\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Enum\SocialProvider;
use Identio\Sdk\IdentioClient;
use Identio\Sdk\Social\ArraySessionStore;
use Identio\Sdk\Social\SocialFlowManager;
use PHPUnit\Framework\TestCase;

final class SocialFlowManagerTest extends TestCase
{
    public function testFacebookSocialFlowStoresStateAndAuthenticatesCallback(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'authorizeUrl' => 'https://oauth.example/authorize',
                'state' => 'expected-state',
                'vkCodeVerifier' => null,
            ], JSON_THROW_ON_ERROR)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'token' => 'jwt-token',
                'user' => [
                    'id' => 8,
                    'email' => 'social@example.com',
                    'confirmed' => true,
                    'active' => true,
                    'domainId' => 42,
                    'values' => [],
                ],
                'registrationRequired' => false,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => HandlerStack::create($mock)]),
        );
        $flow = new SocialFlowManager($client->social, new ArraySessionStore());

        $start = $flow->start(SocialProvider::Facebook);
        $result = $flow->complete(SocialProvider::Facebook, 'oauth-code', 'expected-state');

        self::assertSame('https://oauth.example/authorize', $start->authorizeUrl);
        self::assertTrue($result->isAuthenticated());
        self::assertSame('social@example.com', $result->user?->email);
    }
}
