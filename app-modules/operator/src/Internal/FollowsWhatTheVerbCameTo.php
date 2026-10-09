<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\Presenters\HowAVerbEndedReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\HowTheVerbWent;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that sends a start, a stop or a restart and follows it to the stack's report.
 *
 * A verb answers a handle, and what it came to arrives only through that
 * handle: a listing read afterwards says where the services stand, and cannot
 * say that a start was declined, that it was a rehearsal, or which of the
 * services it waited for never came back. So the handle is held, asked after
 * while the verb runs, and the report drawn once it finishes. The cadence is
 * the using screen's, which polls while this says the verb is working.
 *
 * **It reads the using screen's own `$supervising` and `$storage`**, for the
 * reason {@see LetsGoOfARefusedSession} gives, and lets go of a session the
 * stack refused through that trait, which the screen also uses.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsWhatTheVerbCameTo
{
    /** The verb that was sent, so its report is judged against what it was for. */
    public ?AgreedTo $sent = null;

    /** The handle sending it answered. Not shown, and never kept past this screen. */
    public ?string $took = null;

    /** What became of it, once this frame has asked. */
    public ?HowTheVerbWent $cameTo = null;

    /** What the stack said where it refused the last yes because what it was given for has moved, or nothing. */
    public ?ARefusalAsShown $movedOn = null;

    /** What became of the verb sent here, or that none was. */
    public function whatItCameTo(): HowTheVerbWent
    {
        if ($this->cameTo instanceof HowTheVerbWent) {
            return $this->cameTo;
        }

        // Asking after a verb is a reading of the stack, and a frame that
        // has read it already asks on the next one. The frame that waits
        // draws the verb as running.
        if ($this->took !== null && ! $this->mayReadItsStack()) {
            return new HowAVerbEndedReads()->running();
        }

        return $this->cameTo = $this->followed();
    }

    /**
     * The same question this screen's cadence asks, answered from what it last heard.
     *
     * A handle whose report has not come back yet counts as work in flight.
     */
    public function awaitsAnOutcome(): bool
    {
        $heard = $this->cameTo;

        return $heard instanceof HowTheVerbWent ? $heard->isWorking : $this->took !== null;
    }

    abstract public function stack(): Stack;

    /**
     * Send the verb that was agreed to again, where other work held the stack.
     *
     * Only then: the stack turned it away before acting on it, so nothing it
     * did is done twice. Sent as it was agreed to, under a new key, and only
     * because the operator tapped for it.
     */
    public function tryAgain(): void
    {
        $sent = $this->sent;

        if (! $sent instanceof AgreedTo || ! $this->whatItCameTo()->went->wasHeldByOtherWork()) {
            return;
        }

        $this->tellIt($sent);
    }

    /** Whether this frame may take another reading of the stack, which taking it promises. */
    abstract private function mayReadItsStack(): bool;

    /**
     * Send the verb agreed to, and hold what to follow it by.
     *
     * Just sent, it is running, and the cadence asks after it from there. A
     * refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though the verb were running.
     */
    private function tellIt(AgreedTo $agreed): void
    {
        $stack = $this->stack();
        $this->took = null;
        $this->sent = $agreed;

        $this->cameTo = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheVerbWent => $this->supervising->told($stack, $session, $agreed)->either(
                started: function (Job $job): HowTheVerbWent {
                    $this->took = $job->shown();

                    return new HowAVerbEndedReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowAVerbEndedReads()->met(...)),
            ),
            notHeld: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->signedOut(),
        );
    }

    /** Ask the stack what became of the verb sent, or say that none was. */
    private function followed(): HowTheVerbWent
    {
        $took = $this->took;
        $sent = $this->sent;

        if ($took === null || ! $sent instanceof AgreedTo) {
            return new HowAVerbEndedReads()->notAsked();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheVerbWent => $this->supervising->whatBecameOf($stack, $session, Job::named($took))->either(
                stillRunning: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->running(),
                done: static fn(WhatTheVerbCameTo $report): HowTheVerbWent => new HowAVerbEndedReads()->done($report, $sent->doing()),
                ended: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->ended(),
                met: $this->lettingGoIfRefused($stack, new HowAVerbEndedReads()->met(...)),
                // Refused and offered again, as a repair is: nothing was done,
                // the yes was given for what has since moved, so what the stack
                // said is kept and the verb is put back as a question.
                moved: function (ARefusalInItsWords $why) use ($sent): HowTheVerbWent {
                    $this->took = null;
                    $this->movedOn = new HowARefusalReads()->inItsWords($why);
                    $this->offerAgain($sent);

                    return new HowAVerbEndedReads()->notAsked();
                },
            ),
            notHeld: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->signedOut(),
        );
    }

    /** Put a yes the stack refused because what it was given for has moved back as a question. */
    abstract protected function offerAgain(AgreedTo $sent): void;
}
