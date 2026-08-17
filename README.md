# Identio PHP SDK

Framework-independent PHP client for Identio. The package mirrors the public responsibilities of the Java SDK while remaining usable from Laravel, Symfony and ordinary PHP applications.

## Requirements

- PHP 8.2+
- Guzzle 7

## Installation

```bash
composer require identio/php-sdk
```

Until the package is published, add its Git repository as a Composer VCS repository or use a local path repository.

## Client creation

```php
<?php

use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\IdentioClient;

$identio = new IdentioClient(new IdentioConfig(
    baseUrl: 'https://identio.ru',
    domainId: 42,
    apiToken: $_ENV['IDENTIO_API_TOKEN'],
    apiSecret: $_ENV['IDENTIO_API_SECRET'] ?? null,
));
```

The API token is sent only from the server as `Authorization: Bearer ...`. Never expose it in browser JavaScript.

## Registration with email confirmation

```php
$result = $identio->auth->register(
    email: 'user@example.com',
    password: 'secret-password',
    values: [],
);
```

Identio creates the account and sends its own OTP confirmation email. Registration usually returns no authenticated token until the confirmation link is processed.

```php
$result = $identio->auth->confirm($_GET['code']);

if ($result->isAuthenticated()) {
    $jwt = $result->token;
    $identioUser = $result->user;
}
```

## Email login

```php
$result = $identio->auth->login('user@example.com', 'secret-password');
```

Other email operations:

```php
$identio->auth->resendVerification('user@example.com');
$identio->auth->forgotPassword('user@example.com');
$identio->auth->resetPassword($code, $newPassword);
$identio->auth->updateSelf($userJwt, password: $newPassword, values: []);
$identio->auth->deleteSelf($userJwt);
```

## Profile values

```php
use Identio\Sdk\Dto\ProfileValue;

$values = [
    ProfileValue::string(fieldId: 10, name: 'name', value: 'Igor'),
    ProfileValue::numeric(fieldId: 11, name: 'age', value: 58),
    ProfileValue::date(fieldId: 12, name: 'birthday', value: '1968-01-01'),
];
```

The configured domain API token can also read and update another user's
ordinary profile values from a server-side administrator integration:

```php
$user = $identio->auth->getUser(7);
$updated = $identio->auth->updateUser(7, [
    ProfileValue::string(fieldId: 88, name: 'role', value: 'support'),
]);
```

The host application must authorize the administrator action. The SDK sends a
regular `GET` or `PUT /api/external/domains/{domainId}/users/{userId}` request;
the role remains an ordinary Identio profile value and is never a system DTO
property.

## Social login

Supported providers are Google, Yandex, VK and Facebook.

```php
use Identio\Sdk\Enum\SocialProvider;
use Identio\Sdk\Social\NativeSessionStore;
use Identio\Sdk\Social\SocialFlowManager;

session_start();

$flow = new SocialFlowManager(
    client: $identio->social,
    session: new NativeSessionStore(),
);

$start = $flow->start(SocialProvider::Facebook);
header('Location: ' . $start->authorizeUrl);
exit;
```

Callback:

```php
$result = $flow->complete(
    provider: SocialProvider::Facebook,
    code: $_GET['code'] ?? null,
    state: $_GET['state'] ?? null,
    vkDeviceId: $_GET['device_id'] ?? null,
    providerError: $_GET['error'] ?? null,
);
```

`SocialFlowManager` stores and consumes Identio `state`, checks the ten-minute flow lifetime, verifies provider matching and preserves the VK PKCE verifier.

When Identio returns `registrationRequired = true`, collect the mandatory profile values and finish through the one-time registration token:

```php
$result = $identio->social->completeRegistration(
    provider: SocialProvider::Facebook,
    registrationToken: $result->registrationToken,
    values: $values,
);
```


## Laravel registration

Register one singleton in a service provider:

```php
use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\IdentioClient;

$this->app->singleton(IdentioClient::class, static fn (): IdentioClient => new IdentioClient(
    new IdentioConfig(
        baseUrl: (string) config('services.identio.base_url'),
        domainId: (int) config('services.identio.domain_id'),
        apiToken: (string) config('services.identio.api_token'),
        apiSecret: config('services.identio.api_secret'),
    ),
));
```

For the social flow, implement `Identio\Sdk\Social\SocialSessionStore` as a thin adapter over Laravel's session store. PortalKit can then use the same SDK without copying Identio HTTP contracts into the application.

## Exceptions

All SDK exceptions inherit from `Identio\Sdk\Exception\IdentioException`.

- `TransportException`: network or HTTP-client failure before a response is received.
- `AuthenticationException`: HTTP 401.
- `ForbiddenException`: HTTP 403.
- `NotFoundException`: HTTP 404.
- `ConflictException`: HTTP 409.
- `ValidationException`: HTTP 400 or 422.
- `ServerException`: HTTP 5xx.
- `SocialFlowException`: local OAuth session/state validation failure.

An `ApiException` exposes `statusCode` and the decoded `responseBody`.

## Social-provider update callback verification

The SDK reproduces the Java SDK HMAC-SHA256 signature algorithm:

```php
$valid = $identio->socialProviderUpdateVerifier->verify(
    apiTokenHeader: $_SERVER['HTTP_X_USERID_API_TOKEN'] ?? '',
    signatureHeader: $_SERVER['HTTP_X_USERID_API_SIGNATURE'] ?? '',
    payload: $providerConfig,
);
```

## Tests

```bash
vendor/bin/phpunit
```
