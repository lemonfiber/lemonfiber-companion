<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where handing one person's device over stands, and what to hand them.
 *
 * Every word is the stack's: the reason, the steps, the apps, the devices
 * signed in. What to do next is named rather than said, and this app offers
 * it its own way.
 *
 * **The code is an address and nothing more.** Whoever holds a copy of it can
 * find the server and still has to sign in as somebody.
 */
final readonly class AHandoff
{
    private function __construct(
        private SomebodyInTheHousehold $who,
        private WhereTheHandoffStands $stands,
        private string $reason,
        private WhatTheHandoffNeedsNext $next,
        private WhatToHandThem $handed,
        private AMomentAsWritten $given,
        private TheSignedInDevices $signedIn,
    ) {}

    /**
     * What the stack answered.
     *
     * `reason` is empty where the state says it all, `next` is `Nothing` where
     * nothing is left to do, and `given` reads as unreadable until a code has been given.
     */
    public static function answered(
        SomebodyInTheHousehold $who,
        WhereTheHandoffStands $stands,
        string $reason,
        WhatTheHandoffNeedsNext $next,
        WhatToHandThem $handed,
        AMomentAsWritten $given,
        TheSignedInDevices $signedIn,
    ): self {
        if ($reason !== '' && trim($reason) === '') {
            throw HandoffSaysNothing::about('reason');
        }

        return new self($who, $stands, $reason, $next, $handed, $given, $signedIn);
    }

    /** Who it is for, as the media server spells their account. */
    public function who(): SomebodyInTheHousehold
    {
        return $this->who;
    }

    public function stands(): WhereTheHandoffStands
    {
        return $this->stands;
    }

    /** Why it stands there, in the stack's words, or empty. */
    public function reason(): string
    {
        return $this->reason;
    }

    /** What there is to do next, or `Nothing`. */
    public function next(): WhatTheHandoffNeedsNext
    {
        return $this->next;
    }

    /** What it gives them: the address, the steps and the apps. */
    public function handed(): WhatToHandThem
    {
        return $this->handed;
    }

    /** When a code was first given, as the stack wrote it; unreadable until one was. */
    public function given(): AMomentAsWritten
    {
        return $this->given;
    }

    /** The devices signed in to the account now. */
    public function signedIn(): TheSignedInDevices
    {
        return $this->signedIn;
    }
}
