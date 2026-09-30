<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to hand one person's device the way onto the media server, and following it.
 *
 * One asking both gives the code and says whether a device of theirs has
 * arrived since, so asking again is how the proof is read. Acting and following
 * are one port for the reason {@see KeepingCurrent} gives.
 *
 * **No key rides on the asking.** The first asking writes down when the code
 * was given, and every one after reads that back: two askings are one hand-off,
 * which is what a key would be for.
 */
interface HandingOverADevice
{
    /** Ask where handing their device over stands; the stack takes it on, refuses, or is not reached. */
    public function handOver(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheHandoff;

    /** What became of that asking, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheHandoff;
}
