<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The things the app offers to do with what a stack runs.
 *
 * An enum rather than a method per verb on a port, because these are one
 * question asked several ways: the screen renders a row and the operator picks
 * a verb. A method each would be a code path each where there is one, and the
 * confirmation that is required would have to be written into each.
 *
 * **Stopping and restarting are not the same kind of act.** A stop leaves a
 * thing off until somebody says otherwise; a restart is a stop that intends to
 * come back. An operator whose service is misbehaving wants the second, and a
 * screen that offered only *stop* invites them to do half of it and walk away —
 * which is how a household loses a service for a week because somebody was
 * debugging on a Tuesday.
 *
 * **The value is the operator's word and {@see self::asked()} is
 * lemonfiber's.** That surface offers `up`, `down`, `restart` and `pull`, and a
 * screen asks whether a stack declares each by that name before it draws the
 * button. So the names live here, in one `match`, and the value stays the word
 * a catalogue key is built from: a key is rebuilt from a case's value, and a
 * value of `up` would leave every sentence on this screen out of reach of the
 * check that finds sentences nothing reads. The verb itself is sent as the
 * class the SDK generates for it, so neither word is spelled on the wire.
 *
 * **Starting is the one that disturbs nothing.** Whatever is running goes on
 * running, so the statement about what will be disturbed has nothing to
 * say — and a screen asking for confirmation of a start would be teaching an
 * operator to confirm without reading, which is what makes the stop
 * confirmation worthless.
 */
enum WhatToDoWithIt: string implements AnAction
{
    /** Bring it up. Nothing that is running stops. */
    case Start = 'start';

    /** Take it down and leave it down. */
    case Stop = 'stop';

    /** Take it down meaning to bring it back. */
    case Restart = 'restart';

    /**
     * Fetch a form's images ahead of starting it.
     *
     * Nothing running stops, and nothing comes up: the images are brought
     * onto the machine so a later start does not wait on them. It is offered
     * for a form and never for one service, because the stack fetches by form.
     */
    case Pull = 'pull';

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
     * *stop* are the operator's; they are the same acts, and nothing
     * between here and the socket has to know both.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Start => 'up',
            self::Stop => 'down',
            self::Restart => 'restart',
            self::Pull => 'pull',
        };
    }

    /**
     * Whether doing this takes something away.
     *
     * The line is drawn once, here. A disruptive action states
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
            self::Start, self::Pull => false,
            self::Stop, self::Restart => true,
        };
    }

    /**
     * Whether doing this is meant to leave services up.
     *
     * What such a verb came to is judged by whether everything came back, and
     * what did not is named. A stop is meant to leave them down, and naming
     * what it left down as *not back* would report the stop as a failure.
     */
    public function bringsSomethingUp(): bool
    {
        return match ($this) {
            self::Start, self::Restart => true,
            self::Stop, self::Pull => false,
        };
    }

    /**
     * Whether this fetches images ahead of a start.
     *
     * The one verb whose cost is time and the line rather than something
     * taken away, and the stack reports no bound for it. So the screen says
     * before it runs that it may take long and use a lot of the line, and puts
     * no duration or size of its own on either.
     */
    public function fetchesAhead(): bool
    {
        return $this === self::Pull;
    }

    /**
     * Whether the operator is asked before this is sent.
     *
     * Anything that takes something away, and a fetch, whose cost has to be
     * said before it runs. A start is sent on the tap.
     */
    public function asksFirst(): bool
    {
        return $this->takesSomethingAway() || $this->fetchesAhead();
    }

    /** Whether this can be done to one service, rather than only to a whole form. */
    public function reachesAService(): bool
    {
        return ! $this->fetchesAhead();
    }

}
