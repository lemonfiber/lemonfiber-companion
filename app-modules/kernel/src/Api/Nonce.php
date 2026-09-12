<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strlen;
use function trim;

/**
 * A value nobody can guess and nothing produces twice.
 *
 * What `Entropy` answers with, and the one thing an idempotency key is allowed
 * to be built from. A type rather than a string because a nonce and a stack id
 * are both strings, and a nonce that has been reused is the one mistake that
 * cannot be seen by reading it — every one of them looks like the others.
 *
 * The length floor is the whole of its promise. A short nonce is not a weaker
 * nonce, it is a guessable one: an attacker replaying a command needs only to
 * land on a key the server has already seen, and the search is over whatever
 * length this type permits. Sixteen characters is the floor because the
 * adapter answers thirty-two and a fake that answered less would be testing
 * something the application never sees.
 */
final readonly class Nonce
{
    /**
     * The shortest a nonce may be.
     *
     * Not a security parameter to tune — a floor, so that a fake cannot be
     * shorter than what the adapter produces and quietly widen what the rest
     * of the suite is written against.
     */
    public const int SHORTEST = 16;

    private function __construct(private string $nonce) {}

    /** The one place a string becomes a nonce. */
    public static function of(string $nonce): self
    {
        $trimmed = trim($nonce);

        if (mb_strlen($trimmed) < self::SHORTEST) {
            throw NonceIsGuessable::at(mb_strlen($trimmed));
        }

        return new self($trimmed);
    }

    public function shown(): string
    {
        return $this->nonce;
    }

    public function is(self $other): bool
    {
        return $this->nonce === $other->nonce;
    }
}
