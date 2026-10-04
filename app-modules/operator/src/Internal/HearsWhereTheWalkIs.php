<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\WhatTheWalkSaidSoFar;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Modules\Operator\Internal\Presenters\HowTheStageReads;
use Modules\Operator\Internal\ViewModels\TheStageAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * The stage a running walk is at, held from the stack's event stream while the walk runs.
 *
 * The stack says each step of a walk on its event stream as it happens, and
 * asking after the walk's handle says only that it is still running. So a
 * screen following a walk holds the stream beside the handle, and takes what
 * arrived on the same wakes it asks after the handle on: taking sends nothing
 * to the stack.
 *
 * **Opened by the operator's act and let go of when the walk is over.** The
 * stream is opened when the operator starts a walk, before the walk is asked
 * for, so the first step it says is one this screen hears. It is let go of on
 * the first wake after the walk stops running.
 *
 * **Held only while somebody can see it**, for {@see HoldsItsStacksStream}'s
 * reason: every wake asks the device whether the app is in front, and lets go
 * where it is not, and leaving the screen lets go too.
 *
 * **A session the stream refuses is let go of**, for {@see LetsGoOfARefusedSession}'s
 * reason, which the screen using this also uses.
 *
 * **A silence past the contract's bound is a broken subscription**, let go of on
 * the wake that notices and opened again once {@see WhatTheWalkSaidSoFar} says
 * the break has been waited out.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsWhereTheWalkIs
{
    /**
     * What the subscription has said about the walk so far, and whether it still stands.
     *
     * `public` for the reason {@see HearsHowTheStackIs::$heard} is.
     */
    public ?WhatTheWalkSaidSoFar $stepHeard = null;

    /** The stage the walk is at, as the template draws it. */
    public function stage(): TheStageAsShown
    {
        return new HowTheStageReads()->of($this->walkHeardSoFar(), $this->followsTheWalkWith()->clock->now());
    }

    abstract public function stack(): Stack;

    /** Let go of the walk's subscription as the screen stops, keeping what was heard as no longer current. */
    protected function letGoOfWhatElseItHears(): void
    {
        $this->letGoOfTheWalk();
    }

    /** The ports this screen follows a walk with, handed over by the screen that holds them. */
    abstract protected function followsTheWalkWith(): WhatTheWalkIsFollowedWith;


    /**
     * Start afresh for a walk about to begin: let go of anything held, and open the subscription.
     *
     * A step a previous walk said is not where this one is, so nothing heard
     * before carries over.
     */
    private function listenToANewWalk(): void
    {
        $this->letGoOfTheWalk();
        $this->stepHeard = WhatTheWalkSaidSoFar::nothingYet();

        $this->listenToTheWalk();
    }

    /**
     * Take what the subscription has delivered about the walk, or let go of it.
     *
     * Opens it where it is not open and the break before has been waited out.
     */
    private function listenToTheWalk(): void
    {
        $with = $this->followsTheWalkWith();
        $now = $with->clock->now();
        $held = $this->walkHeardSoFar();

        if (! $with->capture->isInFront()) {
            $this->stepHeard = $held->after($with->hearing->letGo(), $now)->wentAway();

            return;
        }

        if (! $held->mayListen($now)) {
            return;
        }

        $held = $held->after($this->walkHeardFrom($this->stack()), $now);

        $this->stepHeard = $held->hasGoneQuiet($now) ? $held->after($with->hearing->letGo(), $now) : $held;
    }

    /**
     * Let go of the subscription once the walk is no longer running, where one is open.
     *
     * A walk that finished has said its last step, and its lines arrive whole
     * with its record, so there is nothing left on the stream for this screen.
     */
    private function letGoOfAWalkThatIsOver(): void
    {
        if ($this->walkHeardSoFar()->isListening()) {
            $this->letGoOfTheWalk();
        }
    }

    /** Let go of the subscription, keeping what was heard as no longer current. */
    private function letGoOfTheWalk(): void
    {
        $with = $this->followsTheWalkWith();

        $this->stepHeard = $this->walkHeardSoFar()->after($with->hearing->letGo(), $with->clock->now())->wentAway();
    }

    private function walkHeardSoFar(): WhatTheWalkSaidSoFar
    {
        return $this->stepHeard ??= WhatTheWalkSaidSoFar::nothingYet();
    }

    /**
     * What the subscription says, with the session this device holds for the stack.
     *
     * Without one there is nothing to listen with, which reads as a
     * subscription that closed.
     */
    private function walkHeardFrom(Stack $stack): WhatTheWalkSaid
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheWalkSaid => $this->lettingGoOfARefusal(
                $this->followsTheWalkWith()->hearing->whereItIs($stack, $session),
                $stack,
            ),
            notHeld: static fn(): WhatTheWalkSaid => WhatTheWalkSaid::closed(),
        );
    }

    /**
     * What the subscription said, having let go of a session the stack refused on it.
     *
     * The stream refusing the session is the stack refusing it, and a session
     * left in the store would be resumed on the next wake and refused again.
     */
    private function lettingGoOfARefusal(WhatTheWalkSaid $said, Stack $stack): WhatTheWalkSaid
    {
        return $said->either(
            nothing: static fn(): WhatTheWalkSaid => $said,
            alive: static fn(): WhatTheWalkSaid => $said,
            said: static fn(): WhatTheWalkSaid => $said,
            closed: static fn(): WhatTheWalkSaid => $said,
            met: function (Obstacle $why) use ($said, $stack): WhatTheWalkSaid {
                $this->letGoOfTheSession($why, $stack);

                return $said;
            },
        );
    }
}
