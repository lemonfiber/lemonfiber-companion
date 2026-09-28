<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash_equals;
use function preg_match;
use function trim;

/**
 * Which stack, for as long as this app knows it.
 *
 * This exists for one thing: the app holds more than one configured
 * stack, keeps each one's session separate, and must never attribute a reading
 * from one to another. That last clause is not enforceable by care — it is
 * enforceable by everything that belongs to a stack carrying one of these, so
 * that pairing a reading with the wrong stack is a type error rather than a
 * mistake.
 *
 * **Not the address.** Trust is pinned to the stack rather than to where it
 * answers, so the same machine reached over another route is the same stack and
 * must not be re-identified. An address makes a fine key right up to the first
 * DHCP lease, at which point every retained reading silently belongs to
 * something else.
 *
 * Not the certificate either, which a renewal replaces. A machine that comes
 * back with a new certificate is the machine the operator has been using, and
 * an identity derived from its digest would orphan everything held for it at
 * the moment they were told nothing had happened.
 *
 * **Taken from the stack's own pairing material.** The stack mints its
 * identifier once, keeps it beside its configuration, and writes it into every
 * piece of material it issues, so scanning a second code for the same machine
 * names the same stack. An identity minted on this side could not do that:
 * each pairing would name a new machine, and pairing the one already held would
 * add a second row to the list whose whole job is to say which machines are in
 * the house.
 */
final readonly class StackId
{
    private function __construct(private string $id) {}

    /**
     * An identity minted on this side, for a stack no pairing material described.
     *
     * What a stand-in holds: a machine that exists only in this build has no
     * material of its own to name it.
     */
    public static function of(Nonce $nonce): self
    {
        return new self($nonce->shown());
    }

    /**
     * The identity a stack gave itself in the material it issued.
     *
     * Its own constructor rather than {@see rememberedAs()}, because the two
     * fail in different places and a refusal has to say which: material naming
     * no identifier a stack mints is the stack's to reissue, and a blank
     * retained entry is this device's state gone wrong.
     *
     * Exactly the shape a stack mints: sixteen bytes written as thirty-two
     * lower-case hexadecimal characters. Anything else did not come from a
     * stack, and taking it would key a machine on a value no second code from
     * that machine would repeat.
     */
    public static function saidBy(string $said): self
    {
        if (preg_match('/\A[0-9a-f]{32}\z/', $said) !== 1) {
            throw StackIsUnidentified::byItsOwnMaterial();
        }

        return new self($said);
    }

    /**
     * The one place a stored identifier becomes one again.
     *
     * Separate from `of()` and `saidBy()` because they are different acts: one
     * mints an identity, one takes the identity a stack gave itself, and this
     * reads back one that was already held. A single constructor taking a
     * string would make the first two possible by accident, from anywhere, with
     * any string.
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
