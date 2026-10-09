<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where this device keeps the grant it plays with, one per stack, with whom it is for.
 *
 * In the platform's secure store, under the stack's key: a grant plays a
 * member's titles, and the core keeps no copy of it. A grant is handed back
 * only to the member and device it was kept for; asked for by anybody else, it
 * is none.
 *
 * Removing a stack from the phone lets it go, as it does the session.
 */
interface KeepingTheGrant extends ForgetsAStack
{
    /** The grant this device holds on a stack for this member and device, or that it holds none. */
    public function theGrantOn(StackId $stack, TheGrantIsFor $for): TheGrantHeld;

    /** Keep the grant this device was handed on a stack for this member, in place of any before it. */
    public function keepTheGrant(StackId $stack, TheGrantIsFor $for, AGrant $grant): Kept;

    /** Let the grant go, where the door refused it or it lapsed. */
    public function letTheGrantGo(StackId $stack): Kept;
}
