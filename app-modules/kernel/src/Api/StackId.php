<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash_equals;
use function trim;

/**
 * Which stack, for as long as this app knows it.
 *
 * `N1-R11` is what this exists for: the app holds more than one configured
 * stack, keeps each one's session separate, and must never attribute a reading
 * from one to another. That last clause is not enforceable by care — it is
 * enforceable by everything that belongs to a stack carrying one of these, so
 * that pairing a reading with the wrong stack is a type error rather than a
 * mistake.
 *
 * **Not the address.** `N1-R22` pins trust to the stack rather than to where it
 * answers, so the same machine reached over another route is the same stack and
 * must not be re-identified. An address makes a fine key right up to the first
 * DHCP lease, at which point every retained reading silently belongs to
 * something else.
 *
 * Generated on this side rather than taken from the wire. A server that has
 * never met this app cannot have named itself to it, and two stacks that have
 * never met each other could otherwise arrive with the same identifier —
 * `Nonce` is what makes one, and the pairing that creates a stack is where it
 * happens.
 */
final readonly class StackId
{
    private function __construct(private string $id) {}

    public static function of(Nonce $nonce): self
    {
        return new self($nonce->shown());
    }

    /**
     * The one place a stored identifier becomes one again.
     *
     * Separate from `of()` because they are different acts: one mints an
     * identity for a stack being paired, and this reads back one that was
     * already minted. A single constructor taking a string would make the first
     * one possible by accident, from anywhere, with any string.
     */
    public static function rememberedAs(string $id): self
    {
        $trimmed = trim($id);

        if ($trimmed === '') {
            throw StackIsUnidentified::inRetainedState();
        }

        return new self($trimmed);
    }

    /** What retained state stores, and nothing a screen shows. */
    public function stored(): string
    {
        return $this->id;
    }

    /**
     * Whether this is the same stack, compared in constant time.
     *
     * A stack identifier is not a secret and the comparison is still done this
     * way, for the reason `Fingerprint` gives: it sits beside the ones where it
     * matters, and two identity checks written differently invite the wrong one
     * to be copied.
     */
    public function is(self $other): bool
    {
        return hash_equals($this->id, $other->id);
    }
}
