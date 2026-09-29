<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

use function mb_strlen;

/**
 * Thirty-two bytes nobody can guess, which is what a key is made of.
 *
 * What {@see Entropy::aKey()} answers with and what {@see HoldsTheSealKeys}
 * keeps. A type rather than a string for the reason `Nonce` is one, and a
 * stronger one: a key and a nonce are both bytes from the same source, and a
 * nonce goes on the wire in a header while a key must never leave the device.
 *
 * **Exactly thirty-two bytes.** AES-256 takes a key of that length and no
 * other, and HMAC-SHA256 is at full strength at it, so the length is the
 * cipher's and not a preference. A key of any other length is refused where it
 * is built rather than where a cipher first rejects it, which would be at the
 * moment something is being sealed.
 *
 * **Deliberately awkward to print**, as a `Session` is: `__debugInfo` and
 * `JsonSerializable` redact it, and `serialize()` is refused. Anyone holding
 * the key opens everything the phone keeps, so the way it leaks is the way a
 * session does — a `var_dump` in a crash handler, or an object that fell into
 * a payload.
 */
final readonly class KeyMaterial implements JsonSerializable
{
    /** How many bytes a key is: AES-256's key length. */
    public const int BYTES = 32;

    /** What a debugger and `json_encode` see instead of the bytes. */
    private const string HIDDEN = '(a key, hidden)';

    private function __construct(private string $bytes) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['bytes' => self::HIDDEN];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aKey();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aKey();
    }

    /**
     * The one place bytes become a key.
     *
     * Counted as bytes rather than characters: a key is not text, and a count
     * in characters would read two bytes of a multibyte sequence as one.
     */
    public static function of(string $bytes): self
    {
        $length = mb_strlen($bytes, '8bit');

        if ($length !== self::BYTES) {
            throw KeyIsTheWrongLength::at($length);
        }

        return new self($bytes);
    }

    /** The raw bytes, for the cipher that uses them and the store that keeps them. */
    public function bytes(): string
    {
        return $this->bytes;
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return self::HIDDEN;
    }
}
