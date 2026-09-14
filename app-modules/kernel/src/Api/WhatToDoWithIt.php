<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The three things `N2-R7` asks the app to offer.
 *
 * An enum rather than three methods on a port, because these are one question
 * asked three ways: the screen renders a row and the operator picks a verb.
 * Three methods would be three code paths where there is one, and the
 * confirmation `N2-R8` requires would have to be written into each.
 *
 * **Stopping and restarting are not the same kind of act.** A stop leaves a
 * thing off until somebody says otherwise; a restart is a stop that intends to
 * come back. An operator whose service is misbehaving wants the second, and a
 * screen that offered only *stop* invites them to do half of it and walk away —
 * which is how a household loses a service for a week because somebody was
 * debugging on a Tuesday.
 *
 * **The value is the operator's word and {@see self::asked()} is lemonfiber's.**
 * That surface offers `up`, `down` and `restart`, and the SDK deliberately
 * keeps no copy of its list — a name it does not offer is refused by name,
 * which is an answer a caller can act on, where a stale list held there would
 * go wrong in silence. So the three names live here, in one `match`, and the
 * value stays the word a catalogue key is built from: `L7` reconstructs a key
 * from a case's value, and a value of `up` would leave every sentence on this
 * screen unreachable by the rule that finds sentences nothing reads.
 *
 * Which means `->value` must never reach the wire, and the rule that opens the
 * writing door says so by name — see `TheAppOpensOnlyTheseDoorsTest`.
 *
 * **Starting is the one that disturbs nothing.** Whatever is running goes on
 * running, so `N2-R8`'s statement about what will be disturbed has nothing to
 * say — and a screen asking for confirmation of a start would be teaching an
 * operator to confirm without reading, which is what makes the stop
 * confirmation worthless.
 */
enum WhatToDoWithIt: string
{
    /** Bring it up. Nothing that is running stops. */
    case Start = 'start';

    /** Take it down and leave it down. */
    case Stop = 'stop';

    /** Take it down meaning to bring it back. */
    case Restart = 'restart';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()}.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.do.%s', $this->value);
    }

    /**
     * What lemonfiber calls this verb, as the last segment of an action's path.
     *
     * A `match` rather than the value, so the two vocabularies are told apart
     * in one place. `up` and `down` are that surface's words and *start* and
     * *stop* are the operator's; they are the same three acts, and nothing
     * between here and the socket has to know both.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Start => 'up',
            self::Stop => 'down',
            self::Restart => 'restart',
        };
    }

    /**
     * Whether doing this takes something away (`N2-R8`).
     *
     * The line is drawn once, here. `N2-R8` wants a disruptive action to state
     * what it disturbs before it is confirmed, and a screen deciding for itself
     * which verbs are disruptive would eventually ask for a confirmation of a
     * start — which teaches an operator to confirm without reading and makes
     * the stop confirmation worth nothing.
     *
     * A restart counts. It comes back, but the gap is real and the household is
     * in it: *this will be off for a moment* is a sentence somebody may want to
     * hear before agreeing, particularly about something the rest of the stack
     * leans on.
     */
    public function takesSomethingAway(): bool
    {
        return match ($this) {
            self::Start => false,
            self::Stop, self::Restart => true,
        };
    }

}
