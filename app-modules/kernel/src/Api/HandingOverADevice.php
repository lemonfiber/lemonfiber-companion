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
 * **Each asking carries a key of its own**, as every action this app sends
 * does, so one asking sent again where its answer was lost is one asking. Two
 * askings are still one hand-off: the first writes down when the code was
 * given, and every one after reads that back.
 */
interface HandingOverADevice
{
    /** Ask where handing their device over stands; the stack takes it on, refuses, or is not reached. */
    public function handOver(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheHandoff;

    /** What became of that asking, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheHandoff;
}
