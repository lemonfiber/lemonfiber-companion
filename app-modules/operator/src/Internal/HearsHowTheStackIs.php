<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\News\Api\TheTabsMarked;
use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
use Modules\Operator\Internal\Presenters\HowTheTabsAreMarked;
use Modules\Operator\Internal\ViewModels\TheTabsAsMarked;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * The health summary a screen shows, held rather than read.
 *
 * The core publishes the summary on its event stream and nowhere else, so this
 * holds a subscription to it, and holding it is the screen's one read of it.
 * The subscription is opened once the first frame is up, and after that the
 * screen takes what has arrived on the cadence it declares and never waits for
 * more.
 *
 * **It is held only while somebody can see it.** Every wake asks the device
 * whether the app is in front, and lets go where it is not. Leaving the screen
 * lets go too, through {@see stop()}, which is how every way off a screen ends:
 * a push, a pop, a replace, and the platform parking the app.
 *
 * **A silence past the contract's bound is a broken subscription**, let go of on
 * the wake that notices, and opened again on {@see HowOftenAScreenLooks::AfterABreak}'s
 * cadence. {@see WhatWasHeardSoFar} decides both; this carries out what it
 * decides.
 *
 * **Every word it hears is kept for the list.** The list of stacks says each
 * stack's one line from {@see \Modules\Kernel\Api\Standings}, so the word, and
 * when it was heard, goes there as it arrives.
 *
 * **Every summary it hears is kept for the next opening**, through
 * {@see \Modules\Health\Api\KeepingTheLastReading}, which seals it first. A
 * screen opening on a stack starts from that summary, as of when it was read,
 * rather than from nothing.
 *
 * **What the stack names as newest marks the tabs.** The stream says it when a
 * listener arrives and whenever it changes, and the news module answers which
 * tabs hold something new by it, against what the operator has seen. A wake
 * that names nothing leaves the marks as they were.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowTheStackIs
{
    /**
     * What a screen opening this one hands it to have what is wrong opened out.
     *
     * What's new opens a problem here, and the operator arrives on the list of
     * what is wrong rather than having to ask for it.
     */
    public const string WHATS_WRONG_OPENED = 'whats_wrong_opened';

    /**
     * What the subscription has said so far, and whether it still stands.
     *
     * `public` for the reason {@see HowThisStackIs::$answered} is: it is what
     * the component's property syncing writes and what the view reads.
     */
    public ?WhatWasHeardSoFar $heard = null;

    /** Whether the operator has opened the summary out to what it counts. */
    public bool $expanded = false;

    /**
     * Which tabs the stack's newest marks, as last heard.
     *
     * `public` for the reason {@see $heard} is.
     */
    public ?TheTabsMarked $marked = null;

    public function mount(): void
    {
        $this->expanded = $this->expanded || $this->data(self::WHATS_WRONG_OPENED) === true;
        $this->listen();
    }

    /**
     * Take what the subscription has delivered, or let go of it.
     *
     * Opens it where it is not open and the break before has been waited out.
     * Taking sends nothing to the stack; it reads what the stack already sent.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function listen(): void
    {
        $with = $this->listensWith();
        $now = $with->clock->now();
        $held = $this->heardSoFar();

        if (! $with->capture->isInFront()) {
            $this->heard = $held->after($with->hearing->letGo(), $now)->wentAway();

            return;
        }

        if (! $held->mayListen($now)) {
            return;
        }

        $stack = $this->stack();
        $thisWake = $this->heardFrom($stack, $now);
        $this->marked = $this->markedBy($thisWake, $stack);
        $held = $held->after($thisWake, $now);

        if ($held->hasGoneQuiet($now)) {
            $held = $held->after($with->hearing->letGo(), $now);
        }

        $this->heard = $held->stoppedBy(
            nothing: static fn(): WhatWasHeardSoFar => $held,
            met: function (Obstacle $why) use ($held, $stack): WhatWasHeardSoFar {
                $this->letGoOfTheSession($why, $stack);

                return $held;
            },
        );
    }

    /** Which tabs hold something new, and how many, as the bar draws them. */
    public function marks(): TheTabsAsMarked
    {
        return new HowTheTabsAreMarked()->of($this->marked ?? TheTabsMarked::none());
    }

    /** Open the summary out to what it counts, or fold it back. */
    public function expand(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /** The summary as the template draws it. */
    public function summary(): WhatTheOneLineSays
    {
        return new HowTheOneLineReads()->of($this->heardSoFar(), $this->listensWith()->clock->now());
    }

    /**
     * Let go of the subscription whenever this screen stops being the one in front.
     *
     * Every way off a screen ends here: a push, a pop, a replace, and the
     * platform parking the app. What was held stays, marked as no longer
     * current, and a return opens the subscription again at once. The list of
     * stacks the top bar's name opens is let go of with it, having
     * subscriptions of its own.
     */
    public function stop(): void
    {
        $with = $this->listensWith();
        $this->heard = $this->heardSoFar()->after($with->hearing->letGo(), $with->clock->now())->wentAway();
        $this->letTheListOfStacksGo();

        parent::stop();
    }

    abstract public function stack(): Stack;

    /** The ports this screen listens with, handed over by the screen that holds them. */
    abstract protected function listensWith(): WhatItListensWith;

    /**
     * It does: the summary it shows is its stack's own stream.
     *
     * So the list of stacks the top bar's name opens does not ask that stack
     * again, and letting the list go leaves this stream alone.
     */
    protected function holdsItsStacksStream(): bool
    {
        return true;
    }

    /** Let go of what the list of stacks the top bar's name opens holds, which {@see Screens\ChoosesAStack} does. */
    abstract private function letTheListOfStacksGo(): void;

    /**
     * What this screen holds, which on opening is what the phone kept.
     *
     * The summary kept from an earlier session, as of when it was read, or
     * nothing where none was kept: the first frame draws it with its age, and
     * the first summary the subscription carries replaces it.
     */
    private function heardSoFar(): WhatWasHeardSoFar
    {
        return $this->heard ??= $this->listensWith()->keeping->lastKept($this->stack()->id());
    }

    /** The tabs marked by what the stack named as newest in this wake, or as they were where it named nothing. */
    private function markedBy(WhatWasHeard $heard, Stack $stack): TheTabsMarked
    {
        return $heard->theNewest(
            named: fn(TheNewestNamed $newest): TheTabsMarked => $this->noticing->whatTheTabsHold($stack->id(), $newest),
            nothing: fn(): TheTabsMarked => $this->marked ?? TheTabsMarked::none(),
        );
    }

    /**
     * What the subscription says, with the session this device holds for the stack.
     *
     * Without one there is nothing to listen with, which reads as a subscription
     * that closed: the screen waits out a break and looks for a session again.
     */
    private function heardFrom(Stack $stack, Instant $now): WhatWasHeard
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatWasHeard => $this->listensWith()->kept(
                $this->listensWith()->hearing->howItIs($stack, $session),
                $stack,
                $now,
            ),
            notHeld: static fn(): WhatWasHeard => WhatWasHeard::closed(),
        );
    }
}
