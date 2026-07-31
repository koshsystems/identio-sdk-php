<?php

declare(strict_types=1);

namespace Identio\Sdk\Tests;

use Identio\Sdk\Social\SocialAuthClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class SocialAuthClientApiTest extends TestCase
{
    public function test_social_client_exposes_only_authentication_operations(): void
    {
        $methods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(SocialAuthClient::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        self::assertContains('start', $methods);
        self::assertContains('authenticate', $methods);
        self::assertContains('completeRegistration', $methods);
        self::assertNotContains('config', $methods);
        self::assertNotContains('configs', $methods);
    }
}
