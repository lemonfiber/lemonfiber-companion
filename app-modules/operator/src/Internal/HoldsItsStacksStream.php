<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\News\Api\HowMuchIsNew;
use Modules\Operator\Internal\Presenters\HowTheTabsAreMarked;
use Modules\Operator\Internal\ViewModels\TheTabsAsMarked;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * The stack's event stream, held by a screen about that stack while it is in front.
 *
 * Every operator's screen about a stack holds it, through
 * {@see Screens\FindsItsWayAround}, whatever it draws. Holding it is the
 * screen's one read of what the stream carries: the health summary, and what
 * the stack names as newest of each kind. The subscription is opened on the
 * first wake after the first frame is up, and after that the screen takes what
 * has arrived on the cadence it declares and never waits for more.
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
 * **It is held on the screen because nowhere else can hold it.** A task on the
 * background lane runs to completion and cannot be stopped once started, so a
 * subscription there could not be let go of when the operator stops looking. A
 * native WebSocket client speaks another protocol than the stack's server-sent
 * events, and would be a second HTTP client and a second enforcement of the
 * certificate pin. The screen's own runloop stays alive between wakes, and each
 * wake reads the stream through the SDK with a wait of a millisecond, so a wake
 * with nothing to take costs about that and never waits on the stack.
 *
 * **Every summary it hears is kept**, through {@see \Modules\Wayfinding\Api\WhatItListensWith::kept()}:
 * the word for the list of stacks, and the whole summary, sealed, for the next
 * time the stack's Health screen opens.
 *
 * **What the stack names as newest marks the tabs and counts in the menu.**
 * The stream says it when a listener arrives and whenever it changes, and the
 * news module answers how much is new by it, of each kind, against what the
 * operator has seen, and holds it for the life of the process, so every screen
 * about the stack draws the last that any of them heard. A wake that names
 * nothing leaves it as it was.
 *
 * **What answers what is handed to the screen that draws it.** The stream says
 * it when a listener arrives and whenever it changes, and the last of it waits
 * in {@see $whatAnswersWhatHeard} for a screen that draws it to take; every
 * other screen leaves it there. It is kept nowhere on the phone.
 *
 * **It reads the screen's own `$listening` and `$storage`.** That is the
 * coupling, stated here because a trait cannot declare it: the ports the
 * stream is heard with, and the store the session is resumed from. A screen
 * without either is refused by PHPStan, which reads this trait in every screen
 * that uses it. The screen takes `$listening` as protected, because only this
 * trait reads it, and an analyser that does not follow a trait reads a private
 * one as never used.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HoldsItsStacksStream
{
    /**
     * What the subscription has said so far, and whether it still stands.
     *
     * `public` because it is what the component's property syncing writes and
     * what the view reads.
     */
    public ?WhatWasHeardSoFar $heard = null;

    /** What answers what as the stream last said it, until a screen that draws it takes it. */
    protected ?TheLinks $whatAnswersWhatHeard = null;

    /**
     * Take what the subscription has delivered, or let go of it.
     *
     * Opens it where it is not open and the break before has been waited out.
     * Taking sends nothing to the stack; it reads what the stack already sent.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function listen(): void
    {
        $now = $this->listening->clock->now();
        $held = $this->heardSoFar();

        if (! $this->listening->capture->isInFront()) {
            $this->heard = $held->after($this->listening->hearing->letGo(), $now)->wentAway();

            return;
        }

        if (! $held->mayListen($now)) {
            return;
        }

        $stack = $this->stack();
        $thisWake = $this->heardFrom($stack, $now);
        $this->noticeTheNewest($thisWake, $stack);
        $this->handOnWhatAnswersWhat($thisWake);
        $held = $held->after($thisWake, $now);

        if ($held->hasGoneQuiet($now)) {
            $held = $held->after($this->listening->hearing->letGo(), $now);
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
        return new HowTheTabsAreMarked()->of($this->howMuchIsNewHere());
    }

    /**
     * Let go of the subscription whenever this screen stops being the one in front.
     *
     * Every way off a screen ends here: a push, a pop, a replace, and the
     * platform parking the app. What was held stays, marked as no longer
     * current, and a return opens the subscription again at once. What else
     * the screen hears on subscriptions of its own, and the list of stacks the
     * top bar's name opens, are let go of with it.
     */
    public function stop(): void
    {
        $this->heard = $this->heardSoFar()->after($this->listening->hearing->letGo(), $this->listening->clock->now())->wentAway();
        $this->letGoOfWhatElseItHears();
        $this->letTheListOfStacksGo();

        parent::stop();
    }

    abstract public function stack(): Stack;

    /**
     * It does: it holds its stack's own stream.
     *
     * So the list of stacks the top bar's name opens does not ask that stack
     * again, and letting the list go leaves this stream alone.
     */
    protected function holdsItsStacksStream(): bool
    {
        return true;
    }

    /**
     * How much is new on this stack, as last heard by any screen, which the menu counts.
     *
     * Asked of the news module on every frame, so a screen that opens draws
     * what an earlier screen heard at once, and a screen returned to draws
     * what the operator has seen since. Nothing until a stream has named the
     * newest in this process.
     */
    protected function howMuchIsNewHere(): HowMuchIsNew
    {
        return $this->listening->noticing->howMuchWasLastNamed($this->stack()->id());
    }

    /**
     * Let go of what else this screen hears on subscriptions of its own, as it stops.
     *
     * Nothing, for a screen that hears nothing else. A screen that follows a
     * walk or a start says otherwise, through the trait that holds it.
     */
    protected function letGoOfWhatElseItHears(): void {}

    /** Let go of what the list of stacks the top bar's name opens holds, which {@see Screens\ChoosesAStack} does. */
    abstract private function letTheListOfStacksGo(): void;

    /**
     * What this screen holds, which on opening is what the phone kept.
     *
     * The summary kept from an earlier session, as of when it was read, or
     * nothing where none was kept, until the subscription carries a summary
     * that replaces it.
     */
    private function heardSoFar(): WhatWasHeardSoFar
    {
        return $this->heard ??= $this->listening->keeping->lastKept($this->stack()->id());
    }

    /** Hand the news module what the stack named as newest in this wake, where it named anything. */
    private function noticeTheNewest(WhatWasHeard $heard, Stack $stack): void
    {
        $heard->theNewest(
            named: fn(TheNewestNamed $newest): HowMuchIsNew => $this->listening->noticing->howMuchIsNew($stack->id(), $newest),
            nothing: static fn(): HowMuchIsNew => HowMuchIsNew::none(),
        );
    }

    /** Hold what answers what, where this wake said it, for a screen that draws it. */
    private function handOnWhatAnswersWhat(WhatWasHeard $heard): void
    {
        $heard->whatAnswersWhat(
            said: function (TheLinks $links) use ($heard): WhatWasHeard {
                $this->whatAnswersWhatHeard = $links;

                return $heard;
            },
            nothing: static fn(): WhatWasHeard => $heard,
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
            held: fn(Session $session): WhatWasHeard => $this->listening->kept(
                $this->listening->hearing->howItIs($stack, $session),
                $stack,
                $now,
            ),
            notHeld: static fn(): WhatWasHeard => WhatWasHeard::closed(),
        );
    }
}
