<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

/**
 * A value as its owner holds it, before it is sealed or after it is opened.
 *
 * What goes into {@see Sealed::seal()} and comes back out of
 * {@see Sealed::open()}. What a stack said about a household is in here in the
 * clear, so it is a type rather than a string: a store port takes a
 * {@see SealedPayload} and never this, and the two cannot be passed for each
 * other.
 *
 * There is no `shown()`. The one accessor is {@see inTheClear()}, and the name
 * is the warning: reading it anywhere but on the way into the seal or out of
 * it is a line that says what it is doing.
 *
 * **Deliberately awkward to print**, as a `Session` is: `__debugInfo` and
 * `JsonSerializable` redact it, and `serialize()` is refused, so the plaintext
 * does not reach a log line, a payload or a cache entry by accident.
 */
final readonly class Unsealed implements JsonSerializable
{
    /** What a debugger and `json_encode` see instead of the value. */
    private const string HIDDEN = '(unsealed, hidden)';

    private function __construct(private string $value) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => self::HIDDEN];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::somethingUnsealed();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::somethingUnsealed();
    }

    /**
     * The one place a string becomes a value to seal.
     *
     * Kept exactly as given, empty included: sealing does not edit what it
     * seals, and an owner that wrote nothing gets nothing back.
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    /** The value itself, for the seal and for the owner that sealed it. */
    public function inTheClear(): string
    {
        return $this->value;
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return self::HIDDEN;
    }
}
