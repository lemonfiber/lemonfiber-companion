<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The machines this device has been introduced to, between launches.
 *
 * A device holds more than one configured stack, and *none of
 * them* into a screen of its own, so the answer to "what is this device paired
 * with" has to survive the app closing. This is the port that survives it.
 *
 * **Not the same port as {@see SecureStorage}, and not the same store's job
 * either.** That one keeps a session, which the app
 * may hold and must not spread. This keeps the stack — an identity, a name an
 * operator typed, an address and a pinned certificate — which is exactly what
 * the app *must* retain, and what a discard of retained
 * state take with it. Two ports because the two have opposite obligations, and
 * a single "storage" port would be a place for the first piece of code to write
 * one out to take the other with it.
 *
 * **A read and a write, and no delete.** Forgetting a stack is a real operation
 * and it is not here yet, because nothing offers it: an operator removing a
 * paired machine is a screen this app does not have, and a port method nothing
 * calls is a promise no adapter has been held to. It arrives with the screen.
 */
interface Stacks
{
    /**
     * What this device is configured for, as of now.
     *
     * Answers {@see Configured} rather than a list, which is what keeps the
     * the guarantees with the value instead of with each caller: there is no
     * current stack to read, and asking about one this device does not hold
     * refuses rather than substituting.
     *
     * A device that has never been paired, and one whose retained state could
     * not be read, both answer `Configured::none()`. That is deliberate and it
     * is the conservative direction: the first-run screen is the right place to
     * land in either case, and the alternative — treating unreadable state as
     * though some unknown stack were configured — is an app that offers to
     * operate a machine it cannot name.
     */
    public function configured(): Configured;

    /**
     * Whether this device holds any pairing at all.
     *
     * Separate from {@see configured()} because the two are asked at different
     * moments and one of them is asked while the app is shut. It needs to
     * know whether there is anything behind the lock before raising it — a
     * prompt over an empty store protects nothing and teaches an operator that
     * the prompt is noise — and that is read from the store
     * rather than from a flag the app maintains, so that the unlocked first run
     * cannot outlive the empty store.
     *
     * What it must not do is answer by reading the pairings. A locked app that
     * has already loaded the operator's machines to count them has read them,
     * whatever it then does with the number. This asks only whether the record
     * is empty.
     */
    public function holdsAny(): bool;

    /**
     * Write a stack down, or refuse and say why.
     *
     * Answers {@see Remembered} rather than raising, for the reason `C1` gives
     * and for a second one that is specific to this: a pairing this device
     * cannot write down is a pairing that did not happen, and the operator has
     * just watched it appear to succeed. That has to reach a screen, so the
     * refusal is a value rather than an exception somebody forgets to catch.
     *
     * Re-pairing is an ordinary case rather than a conflict: a stack whose
     * identity is already held replaces it, which is what {@see Configured}
     * does with one, and this is why — a machine that comes back on another
     * address, or with a renewed certificate, is the same machine.
     */
    public function remember(Stack $stack): Remembered;
}
