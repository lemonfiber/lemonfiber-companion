<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;
use function mb_strlen;
use function mb_substr;

/**
 * The WebPush authentication secret this phone made for one stack, sealed with the stack's other settings.
 *
 * Printed and serialised as nothing, as a {@see KeyMaterial} is.
 */
final readonly class AWakingSecret
{
    /** What a debugger sees instead of the bytes. */
    private const string HIDDEN = '(a secret, hidden)';

    private function __construct(private string $bytes) {}

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['bytes' => self::HIDDEN];
    }

    /** @return array<string, never> */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aWakingSecret();
    }

    /** @param array<string, never> $data */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aWakingSecret();
    }

    /** A secret made of the first half of a key nobody can guess. */
    public static function from(KeyMaterial $key): self
    {
        return new self(mb_substr($key->bytes(), 0, self::length(), '8bit'));
    }

    /** The secret kept as `$bytes`, refused where it is not sixteen bytes long. */
    public static function of(string $bytes): self
    {
        $length = mb_strlen($bytes, '8bit');

        if ($length !== self::length()) {
            throw AWakeIsUnfit::aSecret($length);
        }

        return new self($bytes);
    }

    /** How many bytes the secret is, as RFC 8291 has it: half a key. */
    public static function length(): int
    {
        return intdiv(KeyMaterial::BYTES, 2);
    }

    /** The secret, as bytes, for the stack it is registered with and the store it is sealed in. */
    public function bytes(): string
    {
        return $this->bytes;
    }
}
