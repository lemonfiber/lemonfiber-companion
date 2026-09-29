<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Sealing what the phone keeps, under a key the platform's secure storage holds.
 *
 * Everything the phone keeps between launches goes through here before it
 * reaches a store: the owner of a value seals it, and a store is handed a
 * {@see SealedPayload} and a {@see SealedStack} and never anything it could
 * read. A store that cannot be handed a plain value is a store that cannot
 * write one to disk.
 *
 * **`standing()` is the one answer that says a key was made.** Sealing,
 * opening and hashing a stack read the key and make one where there is none,
 * as asking for the standing does, and say nothing about it: they answer the
 * question they were asked. So an owner asks for the standing before it seals
 * or opens anything, and that is where it learns that what it kept is gone.
 */
interface Sealed
{
    /** Whether the key is held, was made just now, or cannot be had. */
    public function standing(): SealStanding;

    /**
     * Seal one value, or say why it cannot be.
     *
     * Two seals of the same value differ, so a payload says nothing about
     * whether it holds what another does.
     */
    public function seal(Unsealed $value): Sealing;

    /**
     * Open a payload this phone sealed, or say it does not open.
     *
     * A payload altered by a single character does not open, and nor does one
     * sealed under any other key.
     */
    public function open(SealedPayload $payload): Unsealing;

    /**
     * The keyed hash a store names one stack by.
     *
     * The same for one stack for as long as the key is held, different for two
     * stacks, and never containing the stack's identity. Where no key can be
     * had it is a hash under a key kept nowhere, which matches nothing — and
     * nothing is kept there to match.
     */
    public function stack(StackId $stack): SealedStack;
}
