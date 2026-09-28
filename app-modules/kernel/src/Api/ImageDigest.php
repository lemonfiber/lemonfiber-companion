<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The digest an image is pinned to, which is what makes the pin immutable.
 *
 * A tag can be moved to point at a different image; a digest names the bytes.
 * Carried as the stack spells it (`sha256:` and the hash), and not checked
 * further here: the stack pulls by it, so a digest it could not use is one the
 * stack refuses, not one this app second-guesses.
 */
final readonly class ImageDigest
{
    private function __construct(private string $said) {}

    /** A digest as the stack named it; blank is refused. */
    public static function of(string $said): self
    {
        if (trim($said) === '') {
            throw OriginSaysNothing::about('digest');
        }

        return new self($said);
    }

    public function said(): string
    {
        return $this->said;
    }
}
