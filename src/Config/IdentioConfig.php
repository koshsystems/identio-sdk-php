<?php

declare(strict_types=1);

namespace Identio\Sdk\Config;

use Identio\Sdk\Exception\ConfigurationException;

final readonly class IdentioConfig
{
    public string $baseUrl;
    public int $domainId;
    public string $apiToken;
    public ?string $apiSecret;
    public float $timeoutSeconds;

    public function __construct(
        string $baseUrl,
        int $domainId,
        string $apiToken,
        ?string $apiSecret = null,
        float $timeoutSeconds = 10.0,
    ) {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $apiToken = trim($apiToken);
        $apiSecret = $apiSecret === null ? null : trim($apiSecret);

        if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new ConfigurationException('Identio base URL must be a valid absolute URL.');
        }

        if ($domainId <= 0) {
            throw new ConfigurationException('Identio domain ID must be greater than zero.');
        }

        if ($apiToken === '') {
            throw new ConfigurationException('Identio API token is required.');
        }

        if ($apiSecret === '') {
            $apiSecret = null;
        }

        if ($timeoutSeconds <= 0) {
            throw new ConfigurationException('Identio timeout must be greater than zero.');
        }

        $this->baseUrl = $baseUrl;
        $this->domainId = $domainId;
        $this->apiToken = $apiToken;
        $this->apiSecret = $apiSecret;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function domainUsersPath(): string
    {
        return sprintf('/api/external/domains/%d/users', $this->domainId);
    }
}
