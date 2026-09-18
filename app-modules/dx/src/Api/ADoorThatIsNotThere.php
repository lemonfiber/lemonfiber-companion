<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Sdk\Api\Doors;
use Modules\Sdk\Api\PinnedDoors;

/**
 * A door on a stack that is not running, so a password can be offered to it.
 *
 * {@see AStackThatIsNotThere} makes every reading answer. This makes the one
 * act that is not a reading answer too: the exchange turns the operator's
 * password for a session, through a transport of its own, and until this
 * existed the sign-in screen was the single frame of this application that
 * reached the network with stand-ins on.
 *
 * **Two affordances rather than one, as the pairing is kept apart from the
 * stack.** A device that is signed in is what {@see ASessionThisRunKeeps}
 * arranges, and it arranges it by writing a session down. Signing in is the act
 * that produces one, and somebody looking at the sign-in screens — the password,
 * the refusal, the wait after too many attempts — is looking at the act rather
 * than at its result.
 *
 * @implements StandsIn<Doors>
 */
final readonly class ADoorThatIsNotThere implements StandsIn
{
    public function insteadOf(): string
    {
        return Doors::class;
    }

    /**
     * Built per call, which is what the binding it replaces does.
     *
     * A door is opened for one stack with one credential; an instance held
     * across two would be an object that has already been handed a password,
     * which is the shape the exchange's second clause exists to prevent.
     */
    public function which(): Doors
    {
        return new DoorsThatOpenOnNothing(new PinnedDoors());
    }
}
