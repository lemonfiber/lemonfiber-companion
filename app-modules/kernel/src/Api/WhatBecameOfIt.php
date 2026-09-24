<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What became of one repair the operator agreed to.
 *
 * Agreeing is its own act, and this is what comes back from
 * it — per repair, because a listing agreed to as a whole can come apart: one
 * fix takes, the next finds the disk busy, and a third would overwrite
 * something it will not touch without being told again.
 *
 * **Five cases and only one of them is success.** The four that are not are
 * four different situations with four different answers, and an app reporting
 * them as *it did not work* would send somebody to look at a machine in three
 * of them for no reason. A closed set rather than a message, for the reason
 * {@see Obstacle} gives: a sentence can be reworded by accident and a case
 * cannot, and a `match` over it stops compiling the moment another arrives.
 *
 * **Declared with the failures after the success**, which is the order
 * {@see Conclusion} uses: the case that means nothing is wrong reads first, so
 * a reader meets the ordinary outcome before the exceptions to it.
 */
enum WhatBecameOfIt: string
{
    /** It was put right. */
    case Fixed = 'fixed';

    /** It was attempted and did not work. */
    case FixFailed = 'fix_failed';

    /**
     * It was stopped part-way, and something is left behind.
     *
     * The only case that carries anything besides itself. What was left is on
     * the wire beside this word because an operator cannot act on *stopped*
     * alone — a half-moved library and a half-written configuration file are
     * the same word and very different mornings.
     */
    case Stopped = 'stopped';

    /**
     * It was not agreed to, so it was left alone.
     *
     * The operator's answer rather than the machine's: the stack asks before
     * each repair, and this is the one it was told no about. Told apart from a
     * failure because nothing was attempted, which is a decision rather than a
     * fault — and saying the stack declined it would put somebody's own *no*
     * in the machine's mouth.
     */
    case Declined = 'declined';

    /**
     * Doing it would have overwritten something, so it was not done.
     *
     * The one an operator most needs told apart. Nothing is broken, nothing was
     * attempted, and the machine is protecting something the operator may or
     * may not want kept — which only they can decide, and only if they are told
     * that is the situation.
     */
    case WouldOverwrite = 'would_overwrite';

    /**
     * The operator declared the area it would write unmanaged, so it was left
     * alone.
     *
     * Apart from {@see self::WouldOverwrite}, and the difference is the
     * sentence an operator is owed: that one is the stack declining to write
     * over a change it can see, and this is the stack obeying an instruction it
     * was given. Told the first after writing the second, somebody goes looking
     * for a change they never made.
     */
    case Unmanaged = 'unmanaged';

    /**
     * Whether anything was changed on the machine.
     *
     * The line drawn once rather than at each screen that needs it. Four of
     * these changed nothing at all — declined, would-overwrite and unmanaged
     * never started, and a failed fix is the machine's own report that it did
     * not take — and *stopped* is the one that both failed and left something,
     * so it answers false here and is still the case that needs saying most.
     */
    public function changedSomething(): bool
    {
        return $this === self::Fixed;
    }

    /**
     * Whether it is worth agreeing to again.
     *
     * A failed fix and a stopped one may work on a second attempt. Declined,
     * would-overwrite and unmanaged will not, and offering the operator a button that does
     * exactly what it did last time is the behaviour {@see Standing} refuses:
     * a screen that offers to do something it cannot do teaches people that the
     * app is lying to them.
     */
    public function worthAnotherGo(): bool
    {
        return $this === self::FixFailed || $this === self::Stopped;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, under `health.mended.` rather than `health.` alone:
     * `stopped` and `declined` are adjectives that would read as findings on
     * their own, and the group is what says they are about a repair that was
     * agreed to.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.mended.%s', $this->value);
    }
}
