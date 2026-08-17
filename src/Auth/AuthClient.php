<?php

declare(strict_types=1);

namespace Identio\Sdk\Auth;

use Identio\Sdk\Config\IdentioConfig;
use Identio\Sdk\Dto\AuthResult;
use Identio\Sdk\Dto\ProfileValue;
use Identio\Sdk\Dto\User;
use Identio\Sdk\Http\ApiTransport;
use UnexpectedValueException;

final readonly class AuthClient
{
    public function __construct(
        private ApiTransport $transport,
        private IdentioConfig $config,
    ) {
    }

    /**
     * @param list<ProfileValue> $values
     */
    public function register(string $email, string $password, array $values = []): AuthResult
    {
        return $this->authResult($this->transport->request('POST', $this->basePath(), [
            'email' => $this->normalizeEmail($email),
            'password' => $password,
            'values' => $this->profileValues($values),
        ]));
    }

    public function confirm(string $emailConfirmationCode): AuthResult
    {
        $code = trim($emailConfirmationCode);

        return $this->authResult($this->transport->request(
            'POST',
            $this->basePath() . '/confirm/' . rawurlencode($code),
        ));
    }

    public function login(string $email, string $password): AuthResult
    {
        return $this->authResult($this->transport->request('POST', $this->basePath() . '/login', [
            'email' => $this->normalizeEmail($email),
            'password' => $password,
        ]));
    }

    public function forgotPassword(string $email): void
    {
        $this->transport->request('POST', $this->basePath() . '/forgot-password', [
            'email' => $this->normalizeEmail($email),
        ]);
    }

    public function resetPassword(string $code, string $password): void
    {
        $this->transport->request('POST', $this->basePath() . '/reset-password', [
            'code' => trim($code),
            'password' => $password,
        ]);
    }

    public function resendVerification(string $email): void
    {
        $this->transport->request('POST', $this->basePath() . '/resend-verification', [
            'email' => $this->normalizeEmail($email),
        ]);
    }

    /**
     * @param list<ProfileValue> $values
     */
    public function updateSelf(string $userJwt, ?string $password = null, array $values = []): void
    {
        $this->transport->request('PUT', $this->basePath() . '/me', [
            'password' => $password === null ? null : trim($password),
            'values' => $this->profileValues($values),
        ], trim($userJwt));
    }

    public function deleteSelf(string $userJwt): void
    {
        $this->transport->request('DELETE', $this->basePath() . '/me', bearerToken: trim($userJwt));
    }

    /**
     * Fetch a domain user's profile and ordinary profile values.
     */
    public function getUser(int $userId): User
    {
        $response = $this->transport->request('GET', $this->basePath() . '/' . $userId);

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio user response must be a JSON object.');
        }

        return User::fromArray($response);
    }

    /**
     * Update a domain user's profile values through the protected domain API.
     * The caller is responsible for authorizing the operation in the host application.
     *
     * @param list<ProfileValue> $values
     */
    public function updateUser(int $userId, array $values): User
    {
        $response = $this->transport->request('PUT', $this->basePath() . '/' . $userId, [
            'values' => $this->profileValues($values),
        ]);

        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio user update response must be a JSON object.');
        }

        return User::fromArray($response);
    }

    private function basePath(): string
    {
        return $this->config->domainUsersPath();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
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

    private function authResult(array|int|string|null $response): AuthResult
    {
        if (! is_array($response)) {
            throw new UnexpectedValueException('Identio authentication response must be a JSON object.');
        }

        return AuthResult::fromArray($response);
    }
}
