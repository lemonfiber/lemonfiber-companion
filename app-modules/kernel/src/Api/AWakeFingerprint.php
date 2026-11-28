<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash;
use function mb_strlen;
use function mb_substr;
use function preg_match;

/**
 * An endpoint as a stack names it once it keeps it: the first sixteen lower-case hexadecimal digits of the SHA-256 of the whole address.
 *
 * Enough to tell whether the distributor gave this phone another address, and
 * nothing anybody can wake the phone with.
 */
final readonly class AWakeFingerprint
{
    /** How many hexadecimal digits of the digest a fingerprint is. */
    private const int DIGITS = 16;

    /** Lower-case hexadecimal digits and nothing else. */
    private const string HEXADECIMAL = '/\A[0-9a-f]+\z/';

    private function __construct(private string $digits) {}

    /** The fingerprint as a stack wrote it; anything but sixteen lower-case hexadecimal digits is refused. */
    public static function of(string $digits): self
    {
        if (mb_strlen($digits) !== self::DIGITS || preg_match(self::HEXADECIMAL, $digits) !== 1) {
            throw AWakeIsUnfit::aFingerprint();
        }

        return new self($digits);
    }

    /** The fingerprint of the whole address, as a stack works it out. */
    public static function ofTheAddress(string $address): self
    {
        return new self(mb_substr(hash('sha256', $address), 0, self::DIGITS));
    }

    /** The digits, for keeping on the phone. */
    public function shown(): string
    {
        return $this->digits;
    }

    public function is(self $other): bool
    {
        return $this->digits === $other->digits;
    }
}
