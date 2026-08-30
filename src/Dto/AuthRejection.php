<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

use Identio\Sdk\Http\ApiResponse;

final readonly class AuthRejection
{
    public function __construct(
        public string $code,
        public string $message,
        public int $statusCode,
        /** @var array<string, mixed>|list<mixed>|null */
        public ?array $responseBody = null,
    ) {
    }

    public static function fromLoginResponse(ApiResponse $response): ?self
    {
        if ($response->isSuccessful() || $response->statusCode >= 500) {
            return null;
        }

        $responseBody = $response->responseBody();
        $remoteCode = self::stringValue($responseBody['code'] ?? null);
        $code = self::knownCode($remoteCode);
        $message = $response->message();

        if ($code === null) {
            $code = self::codeFromMessage($response->statusCode, $message);
        }

        if ($code === null) {
            return null;
        }

        $message ??= $response->errorMessage();

        return new self(
            code: $code,
            message: $message,
            statusCode: $response->statusCode,
            responseBody: $responseBody,
        );
    }

    private static function codeFromMessage(int $statusCode, ?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $normalized = self::normalize($message);

        if ($statusCode === 404 && $normalized === self::normalize('User not found')) {
            return 'user_not_found';
        }

        if ($statusCode === 401 && in_array($normalized, [
            self::normalize('Invalid credentials'),
            self::normalize('User with this email is not registered, or email is not confirmed, or email/password do not match.'),
            self::normalize('Пользователь с таким email не зарегистрирован или email не подтвержден или email/пароль не совпадают'),
            self::normalize('Логин/пароль не соответствуют или пользователь не зарегистрирован.'),
        ], true)) {
            return 'invalid_credentials';
        }

        if ($statusCode === 403 && $normalized === self::normalize('Email is not confirmed')) {
            return 'email_not_confirmed';
        }

        if ($statusCode === 403 && $normalized === self::normalize('User is not active')) {
            return 'user_inactive';
        }

        return null;
    }

    private static function knownCode(?string $code): ?string
    {
        $code = $code === null ? null : self::normalize($code);

        return in_array($code, [
            'invalid_credentials',
            'user_not_found',
            'email_not_confirmed',
            'user_inactive',
            'social_registration_password_setup_required',
        ], true) ? $code : null;
    }

    private static function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    private static function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
