<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A value a setting holds or declares, or none.
 *
 * None is apart from an empty value, because a setting may be set to nothing.
 */
final readonly class ASettingValue
{
    private function __construct(private ?string $value) {}

    /** This value, exactly as written. */
    public static function value(string $value): self
    {
        return new self($value);
    }

    /** No value. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /** Whether there is a value. */
    public function isHeld(): bool
    {
        return $this->value !== null;
    }

    /** The value, or blank where there is none. */
    public function said(): string
    {
        return $this->value ?? '';
    }
}
