<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to let somebody in: who is in already, what an invitation would come to, sending it, and taking a password off.
 *
 * One port for the five, for {@see KeepingCurrent}'s reason: they are one
 * conversation, and a port that only rehearsed would leave whoever built the
 * sending half free to reach a client of their own.
 *
 * **Every act is work the stack names and this follows.** The stack answers
 * each with a handle, and the invitation arrives through
 * {@see self::whatBecameOf()} once the work is done. So each answers
 * {@see WhatBecameOfTheInvitation}, whose first arm is the handle. Who is in
 * is a reading, answered at once.
 *
 * **Sending takes an {@see AnInvitationAgreed}**, which only a rehearsal of the
 * same request can produce, so what is sent is what was shown. Nothing here
 * takes, sets or carries a password: taking one off names the person and
 * nothing else, and they choose the next one at the media server.
 */
interface Inviting
{
    /** Read everybody the media server holds an account for, or come away with a reason (`C1`). */
    public function whoIsIn(Stack $stack, Session $session): WhatWasFoundOfTheMembers;

    /** Ask what inviting them would come to, making nothing. */
    public function wouldInvite(Stack $stack, Session $session, AnInvitationAskedFor $asked): WhatBecameOfTheInvitation;

    /** Invite them, as the rehearsal the operator was shown described. */
    public function invite(Stack $stack, Session $session, AnInvitationAgreed $agreed): WhatBecameOfTheInvitation;

    /** Take the password off their account, so they choose a new one; answered with an invitation to hand over. */
    public function takeThePasswordOff(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheInvitation;

    /** What became of work one of the three started, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheInvitation;
}
