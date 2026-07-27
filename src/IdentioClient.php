<?php

declare(strict_types=1);

namespace Identio\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Identio\Sdk\Auth\AuthClient;
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Http\ApiTransport;
use Identio\Sdk\Social\SocialAuthClient;
use Identio\Sdk\Webhook\SocialProviderUpdateVerifier;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class IdentioClient
{
    public AuthClient $auth;
    public SocialAuthClient $social;
    public SocialProviderUpdateVerifier $socialProviderUpdateVerifier;

    public function __construct(
        public IdentioConfig $config,
        ?ClientInterface $http = null,
        ?LoggerInterface $logger = null,
    ) {
        $transport = new ApiTransport(
            $http ?? new Client(),
            $config,
            $logger ?? new NullLogger(),
        );

        $this->auth = new AuthClient($transport, $config);
        $this->social = new SocialAuthClient($transport, $config);
        $this->socialProviderUpdateVerifier = new SocialProviderUpdateVerifier($config);
    }
}
