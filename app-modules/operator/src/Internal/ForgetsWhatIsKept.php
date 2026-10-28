<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Operator\Internal\Presenters\HowForgettingReads;
use Modules\Operator\Internal\ViewModels\TheForgettingTurnedOutToBe;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Removing everything lemonfiber keeps, as its own act on the screen that lists it: asked about first, and the yes sent only with that list shown.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\Forgetting} as a protected `$forgetting`.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ForgetsWhatIsKept
{
    /** The list of what would go, while it is on the screen to be agreed to. */
    public ?TheForgetting $shownToForget = null;

    /** Whether the yes has been sent. */
    public bool $forgettingAgreed = false;

    /** The handle of the work being followed, where there is one. */
    public ?string $forgettingFollowed = null;

    public ?TheForgettingTurnedOutToBe $forgettingGoing = null;

    /** Ask what removing everything would take, removing nothing. */
    public function whatWouldGo(): void
    {
        $this->shownToForget = null;
        $this->forgettingAgreed = false;
        $this->forgettingFollowed = null;
        $this->forgettingGoing = $this->forgettingPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->forgetting->whatWouldGo($stack, $session));
    }

    /** Remove everything, against the list on the screen; nothing is sent without that list. */
    public function forgetEverything(): void
    {
        $shown = $this->shownToForget;

        if (! $shown instanceof TheForgetting || ! $shown->got()->awaitsTheYes()) {
            return;
        }

        $agreed = ForgettingAgreed::after($shown);
        $this->shownToForget = null;
        $this->forgettingAgreed = true;
        $this->forgettingGoing = $this->forgettingPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->forgetting->forget($stack, $session, $agreed));
    }

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileForgettingRuns(): void
    {
        if ($this->howForgettingIsGoing()->stands->isWorking()) {
            $this->forgettingGoing = null;
        }
    }

    public function howForgettingIsGoing(): TheForgettingTurnedOutToBe
    {
        $following = $this->forgettingFollowed;

        return $this->forgettingGoing ??= $following === null
            ? new HowForgettingReads()->notAsked()
            : $this->forgettingPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->forgetting->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<TheForgetting> $asking */
    private function forgettingPut(Closure $asking): TheForgettingTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheForgettingTurnedOutToBe => $this->forgettingShown($asking($stack, $session), $stack),
            notHeld: static fn(): TheForgettingTurnedOutToBe => new HowForgettingReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<TheForgetting> $became
     */
    private function forgettingShown(WhatBecameOfTheWork $became, Stack $stack): TheForgettingTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheForgettingTurnedOutToBe {
                $this->forgettingFollowed = $job->shown();

                return new HowForgettingReads()->running();
            },
            answered: function (TheForgetting $forgetting): TheForgettingTurnedOutToBe {
                $this->forgettingFollowed = null;
                $this->shownToForget = $forgetting->got()->awaitsTheYes() && ! $this->forgettingAgreed ? $forgetting : null;

                return new HowForgettingReads()->answered($forgetting);
            },
            ended: function (): TheForgettingTurnedOutToBe {
                $this->forgettingFollowed = null;

                return new HowForgettingReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheForgettingTurnedOutToBe {
                $this->forgettingFollowed = null;

                return new HowForgettingReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheForgettingTurnedOutToBe {
                $this->forgettingFollowed = null;
                $this->letGoOfTheSession($why, $stack);

                return new HowForgettingReads()->met($why, $this->forgettingAgreed);
            },
        );
    }
}
