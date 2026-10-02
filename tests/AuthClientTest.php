<?php

declare(strict_types=1);

namespace Identio\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Dto\ProfileValue;
use Identio\Sdk\Exception\AuthenticationException;
use Identio\Sdk\IdentioClient;
use Psr\Log\AbstractLogger;
use PHPUnit\Framework\TestCase;
use Stringable;

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
                    'values' => [[
                        'fieldId' => 88,
                        'name' => 'role',
                        'type' => 'STRING',
                        'stringValue' => 'support',
                    ]],
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
        self::assertSame('support', $result->user?->values[0]->stringValue);
        self::assertSame('/api/external/domains/42/users/login', $history[0]['request']->getUri()->getPath());
        self::assertSame('Bearer domain-token', $history[0]['request']->getHeaderLine('Authorization'));
        self::assertSame('user@example.com', json_decode((string) $history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR)['email']);
    }

    public function testUpdateUserSendsProfileValuesThroughDomainApi(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => 7,
                'email' => 'user@example.com',
                'confirmed' => true,
                'active' => true,
                'domainId' => 42,
                'values' => [[
                    'fieldId' => 88,
                    'name' => 'role',
                    'type' => 'STRING',
                    'stringValue' => 'support',
                ]],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => $stack]),
        );

        $result = $client->auth->updateUser(7, [ProfileValue::string(88, 'role', 'support')]);

        self::assertSame('support', $result->values[0]->stringValue);
        self::assertSame('/api/external/domains/42/users/7', $history[0]['request']->getUri()->getPath());
        self::assertSame('PUT', $history[0]['request']->getMethod());
        self::assertSame(
            'support',
            json_decode((string) $history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR)['values'][0]['stringValue'],
        );
    }

    public function testGetUserReadsProfileValuesThroughDomainApi(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => 7,
                'email' => 'user@example.com',
                'confirmed' => true,
                'active' => true,
                'domainId' => 42,
                'values' => [[
                    'fieldId' => 88,
                    'name' => 'role',
                    'type' => 'STRING',
                    'stringValue' => 'admin',
                ]],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => $stack]),
        );

        $result = $client->auth->getUser(7);

        self::assertSame(7, $result->id);
        self::assertSame('admin', $result->values[0]->stringValue);
        self::assertSame('/api/external/domains/42/users/7', $history[0]['request']->getUri()->getPath());
        self::assertSame('GET', $history[0]['request']->getMethod());
        self::assertSame('Bearer domain-token', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function testDeleteNotConfirmedByEmailUsesTheDomainApiAndNormalizesTheEmail(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '1'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => $stack]),
        );

        $client->auth->deleteNotConfirmedByEmail(' USER@EXAMPLE.COM ');

        self::assertSame('/api/external/domains/42/users/not-confirmed/user%40example.com', $history[0]['request']->getUri()->getPath());
        self::assertSame('', $history[0]['request']->getUri()->getQuery());
        self::assertSame('DELETE', $history[0]['request']->getMethod());
        self::assertSame('Bearer domain-token', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function testLoginReturnsMissingUserAsARejectedResultWithoutWarning(): void
    {
        $logger = new RecordingLogger();
        $client = $this->clientWithResponse(
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"User not found"}'),
            $logger,
        );

        $result = $client->auth->login('missing@example.com', 'secret');

        self::assertFalse($result->isAuthenticated());
        self::assertTrue($result->isRejected());
        self::assertNull($result->token);
        self::assertNull($result->user);
        self::assertSame('user_not_found', $result->rejection?->code);
        self::assertSame('User not found', $result->rejection?->message);
        self::assertSame(404, $result->rejection?->statusCode);
        self::assertSame([], array_values(array_filter(
            $logger->records,
            static fn (array $record): bool => $record['level'] === 'warning',
        )));
    }

    public function testLoginReturnsInvalidPasswordAsARejectedResultWithoutWarning(): void
    {
        $client = $this->clientWithResponse(
            new Response(401, ['Content-Type' => 'application/json'], '{"message":"Invalid credentials"}'),
        );

        $result = $client->auth->login('user@example.com', 'wrong-password');

        self::assertTrue($result->isRejected());
        self::assertSame('invalid_credentials', $result->rejection?->code);
        self::assertSame('Invalid credentials', $result->message);
        self::assertSame(401, $result->rejection?->statusCode);
    }

    public function testLoginReturnsUnconfirmedUserAsARejectedResult(): void
    {
        $client = $this->clientWithResponse(
            new Response(403, ['Content-Type' => 'application/json'], '{"message":"Email is not confirmed"}'),
        );

        $result = $client->auth->login('user@example.com', 'secret');

        self::assertTrue($result->isRejected());
        self::assertSame('email_not_confirmed', $result->rejection?->code);
        self::assertSame('Email is not confirmed', $result->rejection?->message);
    }

    public function testLoginReturnsInactiveUserAsARejectedResult(): void
    {
        $client = $this->clientWithResponse(
            new Response(403, ['Content-Type' => 'application/json'], '{"message":"User is not active"}'),
        );

        $result = $client->auth->login('user@example.com', 'secret');

        self::assertTrue($result->isRejected());
        self::assertSame('user_inactive', $result->rejection?->code);
        self::assertSame('User is not active', $result->rejection?->message);
    }

    public function testLoginKeepsUnexpectedUnauthorizedResponseAsAnException(): void
    {
        $logger = new RecordingLogger();
        $client = $this->clientWithResponse(new Response(401), $logger);

        try {
            $client->auth->login('user@example.com', 'secret');
            self::fail('An unexpected unauthorized response must throw.');
        } catch (AuthenticationException) {
            self::assertCount(1, array_filter(
                $logger->records,
                static fn (array $record): bool => $record['level'] === 'warning',
            ));
        }

    }

    private function clientWithResponse(Response $response, ?RecordingLogger $logger = null): IdentioClient
    {
        return new IdentioClient(
            new IdentioConfig('https://identio.example', 42, 'domain-token'),
            new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]),
            $logger,
        );
    }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /**
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
