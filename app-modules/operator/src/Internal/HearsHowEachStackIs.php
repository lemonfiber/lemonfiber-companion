<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\ViewModels\WhatTheLaunchWas;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Each stack's one line, heard on its own subscription while the list is open.
 *
 * The list's first frame is drawn from what was kept, with when it was heard.
 * The subscriptions open on the first wake after it, so nothing is reached
 * before that frame is up, and every word a subscription says goes into
 * {@see \Modules\Kernel\Api\Standings}, which is what the rows read.
 *
 * **Only a stack this device is signed into as its operator is listened to.** A
 * stack with no session has nothing to listen with, and a member's session is
 * not the one the stack's summary is for.
 *
 * **Nothing is reached while the lock stands**, nor on a launch that found no
 * network: the list is then drawing nothing a subscription could add to.
 *
 * Every other rule is the stack's own screen's, per stack: it is held only
 * while somebody can see it, a silence past the contract's bound breaks it, and
 * a broken one is opened again on {@see HowOften::AfterABreak}'s cadence.
 * Letting go of a quiet one lets go of every stream the list holds, because
 * {@see \Modules\Kernel\Api\Hearing::letGo()} takes no stack; the others
 * open again on their next wake.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowEachStackIs
{
    /**
     * What each stack's subscription has said so far.
     *
     * `public` because it is what the component's property syncing writes.
     */
    public ?WhatEachStackSaidSoFar $heardFromEach = null;

    /**
     * Take what each subscription has delivered, or let go of them all.
     *
     * Opens a stack's subscription where it is not open and its break has been
     * waited out. Taking sends nothing to the stack.
     */
    #[Poll(HowOften::WHILE_LISTENING_MS)]
    public function listen(): void
    {
        // Only a launch that found a stack to open on reaches one. A locked
        // launch, an unpaired one and one with no network did not.
        if ($this->howItOpened()->opensOn === '') {
            return;
        }

        $with = $this->listensWith();
        $now = $with->clock->now();

        if (! $with->capture->isInFront()) {
            $with->hearing->letGo();
            $this->heardFromEach = $this->heardFromEachSoFar()->wentAway();

            return;
        }

        foreach ($this->configured() as $stack) {
            $this->heardFromEach = $this->storage->resume($stack->id())->either(
                held: fn(Session $session, Whose $whose): WhatEachStackSaidSoFar => $whose->either(
                    operator: fn(): WhatEachStackSaidSoFar => $this->heardFromEachSoFar()
                        ->with($stack->id(), $this->listenedTo($stack, $session, $now)),
                    member: fn(): WhatEachStackSaidSoFar => $this->heardFromEachSoFar(),
                ),
                notHeld: fn(): WhatEachStackSaidSoFar => $this->heardFromEachSoFar(),
            );
        }
    }

    /**
     * Let go of every subscription whenever the list stops being the screen in front.
     *
     * A return opens them again at once.
     */
    public function stop(): void
    {
        $this->listensWith()->hearing->letGo();
        $this->heardFromEach = $this->heardFromEachSoFar()->wentAway();

        parent::stop();
    }

    abstract public function howItOpened(): WhatTheLaunchWas;

    abstract public function configured(): Configured;

    /** The ports the list listens with, handed over by the screen that holds them. */
    abstract protected function listensWith(): WhatItListensWith;

    private function heardFromEachSoFar(): WhatEachStackSaidSoFar
    {
        return $this->heardFromEach ??= WhatEachStackSaidSoFar::nothingYet();
    }

    /** What one stack's subscription holds after this wake. */
    private function listenedTo(Stack $stack, Session $session, Instant $now): WhatWasHeardSoFar
    {
        $held = $this->heardFromEachSoFar()->from($stack->id());

        if (! $held->mayListen($now)) {
            return $held;
        }

        $hearing = $this->listensWith()->hearing;
        $held = $held->after($this->listensWith()->kept($hearing->howItIs($stack, $session), $stack, $now), $now);

        if ($held->hasGoneQuiet($now)) {
            $held = $held->after($hearing->letGo(), $now);
        }

        return $held->stoppedBy(
            nothing: static fn(): WhatWasHeardSoFar => $held,
            met: function (Obstacle $why) use ($held, $stack): WhatWasHeardSoFar {
                $this->letGoOfTheSession($why, $stack);

                return $held;
            },
        );
    }
}
