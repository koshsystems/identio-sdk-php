<?php

declare(strict_types=1);

namespace Identio\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\IdentioClient;
use PHPUnit\Framework\TestCase;

final class AuthClientTest extends TestCase
{
    public function testLoginMapsResponseAndUsesDomainToken(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'token' => 'jwt-token',
                'user' => [
                    'id' => 7,
                    'email' => 'user@example.com',
                    'confirmed' => true,
                    'active' => true,
                    'domainId' => 42,
                    'values' => [],
                ],
                'registrationRequired' => false,
                'registrationToken' => null,
                'message' => null,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => $stack]),
        );

        $result = $client->auth->login(' USER@EXAMPLE.COM ', 'secret');

        self::assertTrue($result->isAuthenticated());
        self::assertSame(7, $result->user?->id);
        self::assertSame('user@example.com', $result->user?->email);
        self::assertSame('/api/external/domains/42/users/login', $history[0]['request']->getUri()->getPath());
        self::assertSame('Bearer domain-token', $history[0]['request']->getHeaderLine('Authorization'));
        self::assertSame('user@example.com', json_decode((string) $history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR)['email']);
    }
}
