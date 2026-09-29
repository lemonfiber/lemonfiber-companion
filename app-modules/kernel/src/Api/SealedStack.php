<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A stack as a store may name it: a keyed hash of its identity.
 *
 * What {@see Sealed::stack()} answers with. A kept row says which stack it
 * belongs to with this and never with the stack's identity, name or address,
 * so a row read off the disk cannot be tied to a stack by anybody without the
 * key. The same stack under the same key is the same hash, which is what lets a
 * store find a stack's rows again.
 *
 * A type rather than a string so that a store port cannot be handed a
 * {@see StackId} where this belongs: the two are both strings, and the mistake
 * is the one that writes a stack's identity to disk in the clear.
 */
final readonly class SealedStack
{
    private function __construct(private string $hash) {}

    /** The one place a string becomes a stack's keyed hash. */
    public static function of(string $hash): self
    {
        return new self($hash);
    }

    /** What a store writes. */
    public function forTheStore(): string
    {
        return $this->hash;
    }
}
