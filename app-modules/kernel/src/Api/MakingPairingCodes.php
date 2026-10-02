<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack for a pairing code another phone adds it with, and following it.
 *
 * Acting and following are one port for the reason {@see KeepingCurrent}
 * gives. It takes a stack and a session rather than a client, so the session
 * and the pinning cannot be paired up wrongly by a caller.
 *
 * **Each asking carries a key of its own**, as every action this app sends
 * does, so one asking sent again where its answer was lost makes one code.
 * Asking twice is two codes, of which the newer is the one shown.
 */
interface MakingPairingCodes
{
    /** Ask for a fresh code; the stack takes it on, refuses, or is not reached. */
    public function make(Stack $stack, Session $session): WhatBecameOfThePairingCode;

    /** What became of a code asked for, by the handle asking for it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfThePairingCode;
}
