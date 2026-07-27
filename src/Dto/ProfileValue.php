<?php

declare(strict_types=1);

namespace Identio\Sdk\Dto;

use DateTimeInterface;

final readonly class ProfileValue
{
    public function __construct(
        public ?int $fieldId = null,
        public ?string $name = null,
        public ?string $type = null,
        public bool $mandatory = false,
        public ?string $stringValue = null,
        public ?string $numericValue = null,
        public ?bool $booleanValue = null,
        public ?int $integerValue = null,
        public int|float|string|null $decimalValue = null,
        public ?string $dateValue = null,
        public ?string $timeValue = null,
        public ?string $timestampValue = null,
    ) {
    }

    public static function string(?int $fieldId, ?string $name, string $value): self
    {
        return new self(fieldId: $fieldId, name: $name, stringValue: trim($value));
    }

    public static function numeric(?int $fieldId, ?string $name, string|int|float $value): self
    {
        return new self(fieldId: $fieldId, name: $name, numericValue: (string) $value);
    }

    public static function date(?int $fieldId, ?string $name, DateTimeInterface|string $value): self
    {
        return new self(
            fieldId: $fieldId,
            name: $name,
            dateValue: $value instanceof DateTimeInterface ? $value->format('Y-m-d') : trim($value),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fieldId: isset($data['fieldId']) ? (int) $data['fieldId'] : null,
            name: self::nullableString($data['name'] ?? null),
            type: self::nullableString($data['type'] ?? null),
            mandatory: (bool) ($data['mandatory'] ?? false),
            stringValue: self::nullableString($data['stringValue'] ?? null),
            numericValue: self::nullableString($data['numericValue'] ?? null),
            booleanValue: array_key_exists('booleanValue', $data) && $data['booleanValue'] !== null ? (bool) $data['booleanValue'] : null,
            integerValue: isset($data['integerValue']) ? (int) $data['integerValue'] : null,
            decimalValue: $data['decimalValue'] ?? null,
            dateValue: self::nullableString($data['dateValue'] ?? null),
            timeValue: self::nullableString($data['timeValue'] ?? null),
            timestampValue: self::nullableString($data['timestampValue'] ?? null),
        );
    }

    /**
     * Payload accepted by Identio registration and profile-update endpoints.
     *
     * @return array<string, mixed>
     */
    public function toRequestArray(): array
    {
        return array_filter([
            'fieldId' => $this->fieldId,
            'name' => self::nullableString($this->name),
            'stringValue' => self::nullableString($this->stringValue),
            'numericValue' => self::nullableString($this->numericValue),
            'dateValue' => self::nullableString($this->dateValue),
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function hasValue(): bool
    {
        return $this->stringValue !== null
            || $this->numericValue !== null
            || $this->booleanValue !== null
            || $this->integerValue !== null
            || $this->decimalValue !== null
            || $this->dateValue !== null
            || $this->timeValue !== null
            || $this->timestampValue !== null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
