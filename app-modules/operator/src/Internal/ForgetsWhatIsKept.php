<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowForgettingReads;
use Modules\Operator\Internal\ViewModels\TheForgettingTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

/**
 * Removing everything lemonfiber keeps, as its own act on the screen that lists it: asked about first, and the yes sent only with that list shown.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\Forgetting} as a protected `$forgetting`.
 * Asking and removing each answer at once or as a job, followed by
 * {@see FollowsOneJobAtATime}, which shows what the job says while it runs.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ForgetsWhatIsKept
{
    /** @use FollowsOneJobAtATime<TheForgettingTurnedOutToBe> */
    use FollowsOneJobAtATime;

    /** The list of what would go, while it is on the screen to be agreed to. */
    public ?TheForgetting $shownToForget = null;

    /** Whether the yes has been sent. */
    public bool $forgettingAgreed = false;

    /** Ask what removing everything would take, removing nothing. */
    public function whatWouldGo(): void
    {
        $this->shownToForget = null;
        $this->forgettingAgreed = false;
        $this->forget();
        $this->reported($this->forgettingPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->forgetting->whatWouldGo($stack, $session)));
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
        $this->forget();
        $this->reported($this->forgettingPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->forgetting->forget($stack, $session, $agreed)));
    }

    public function howForgettingIsGoing(): TheForgettingTurnedOutToBe
    {
        return $this->whatBecameOfTheJob();
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheForgettingTurnedOutToBe
    {
        return $this->forgettingShown($this->forgetting->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): TheForgettingTurnedOutToBe
    {
        return new HowForgettingReads()->notAsked();
    }

    protected function withNoSessionHeld(): TheForgettingTurnedOutToBe
    {
        return new HowForgettingReads()->signedOut();
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
                $this->follow($job);

                return new HowForgettingReads()->running();
            },
            answered: function (TheForgetting $forgetting): TheForgettingTurnedOutToBe {
                $this->shownToForget = $forgetting->got()->awaitsTheYes() && ! $this->forgettingAgreed ? $forgetting : null;

                return new HowForgettingReads()->answered($forgetting);
            },
            ended: fn(?WhyTheWorkEnded $why): TheForgettingTurnedOutToBe => new HowForgettingReads()->ended($why),
            refused: fn(ARefusalInItsWords $why): TheForgettingTurnedOutToBe => new HowForgettingReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheForgettingTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowForgettingReads()->met($why, $this->forgettingAgreed);
            },
        );
    }
}
