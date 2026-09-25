<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * The health summary a screen shows, held rather than read.
 *
 * The core publishes the summary on its event stream and nowhere else, so this
 * holds a subscription to it, and holding it is the screen's one read of it.
 * The subscription is opened once the first frame is up, and after that the
 * screen takes what has arrived on the cadence it states and never waits for
 * more.
 *
 * **It is held only while somebody can see it.** Every wake asks the device
 * whether the app is in front, and lets go where it is not. Leaving the screen
 * lets go too, through {@see stop()}, which is how every way off a screen ends:
 * a push, a pop, a replace, and the platform parking the app.
 *
 * **A silence past the contract's bound is a broken subscription**, let go of on
 * the wake that notices, and opened again on {@see HowOften::AfterABreak}'s
 * cadence. {@see WhatWasHeardSoFar} decides both; this carries out what it
 * decides.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowTheStackIs
{
    /**
     * What the subscription has said so far, and whether it still stands.
     *
     * `public` for the reason {@see HowThisStackIs::$answered} is: it is what
     * the component's property syncing writes and what the view reads.
     */
    public ?WhatWasHeardSoFar $heard = null;

    /** Whether the operator has opened the summary out to what it counts. */
    public bool $expanded = false;

    public function mount(): void
    {
        $this->listen();
    }

    /**
     * Take what the subscription has delivered, or let go of it.
     *
     * Opens it where it is not open and the break before has been waited out.
     * Taking sends nothing to the stack; it reads what the stack already sent.
     */
    #[Poll(HowOften::WHILE_LISTENING_MS)]
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
        $held = $held->after($this->heardFrom($stack), $now);

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
     * How often this screen looks at what it holds, or how soon it opens again.
     *
     * The one the template states is the one this screen is keeping: while a
     * subscription is open it is looked at on one cadence, and while it is
     * broken it is opened again on the other.
     */
    public function cadence(): HowOften
    {
        return $this->heardSoFar()->isListening() ? HowOften::WhileListening : HowOften::AfterABreak;
    }

    /**
     * Let go of the subscription whenever this screen stops being the one in front.
     *
     * Every way off a screen ends here: a push, a pop, a replace, and the
     * platform parking the app. What was held stays, marked as no longer
     * current, and a return opens the subscription again at once.
     */
    public function stop(): void
    {
        $with = $this->listensWith();
        $this->heard = $this->heardSoFar()->after($with->hearing->letGo(), $with->clock->now())->wentAway();

        parent::stop();
    }

    abstract public function stack(): Stack;

    /** The ports this screen listens with, handed over by the screen that holds them. */
    abstract protected function listensWith(): WhatItListensWith;

    private function heardSoFar(): WhatWasHeardSoFar
    {
        return $this->heard ??= WhatWasHeardSoFar::nothingYet();
    }

    /**
     * What the subscription says, with the session this device holds for the stack.
     *
     * Without one there is nothing to listen with, which reads as a subscription
     * that closed: the screen waits out a break and looks for a session again.
     */
    private function heardFrom(Stack $stack): WhatWasHeard
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatWasHeard => $this->listensWith()->hearing->howItIs($stack, $session),
            notHeld: static fn(): WhatWasHeard => WhatWasHeard::closed(),
        );
    }
}
