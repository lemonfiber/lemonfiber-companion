<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_key_exists;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\Presenters\HowAVerbEndedReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\AStartLineAsShown;
use Modules\Operator\Internal\ViewModels\HowTheVerbWent;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that sends a start, a stop or a restart and follows it to the stack's report.
 *
 * A verb answers a handle, and what it came to arrives only through that
 * handle: a listing read afterwards says where the services stand, and cannot
 * say that a start was declined, that it was a rehearsal, or which of the
 * services it waited for never came back. So the job is followed through
 * {@see FollowsOneJobAtATime}, asked after while the verb runs, and the report
 * drawn once it finishes.
 *
 * **It hears no job lines.** What a start or a restart waits on is said on the
 * start's own stream, and the newest line replaces the one before; that line
 * is the verb's only progress. It is taken on the wakes the job is asked after
 * on, only while the verb runs and the screen is in front, and let go of once
 * it has finished.
 *
 * **It reads the using screen's own `$supervising`, `$hearingTheStart` and
 * `$storage`**, for the reason {@see LetsGoOfARefusedSession} gives, and lets go
 * of a session the stack refused through that trait, which the screen also
 * uses. The screen takes `$hearingTheStart` as protected, because only this
 * trait reads it, and an analyser that does not follow a trait reads a private
 * one as never used.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsWhatTheVerbCameTo
{
    /** @use FollowsOneJobAtATime<HowTheVerbWent> */
    use FollowsOneJobAtATime;
    use CountsTheAskOnItsWake;

    /** The verb that was sent, so its report is judged against what it was for. */
    public ?AgreedTo $sent = null;

    /** What the stack said where it refused the last yes because what it was given for has moved, or nothing. */
    public ?ARefusalAsShown $movedOn = null;

    /**
     * The stack's last line for what a start sent from here is waiting for.
     *
     * Kept between frames because the newest line replaces the one before,
     * and a wake that heard nothing new still has the last one to show.
     */
    public string $waitsOn = '';

    /** What became of the verb sent here, or that none was. */
    public function whatItCameTo(): HowTheVerbWent
    {
        if ($this->follows() && ! array_key_exists(TheOnlyJob::Followed->name, $this->became) && ! $this->mayReadItsStack()) {
            return new HowAVerbEndedReads()->running();
        }

        return $this->whatBecameOfTheJob();
    }

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

        if (! $sent instanceof AgreedTo || ! $this->whatItCameTo()->stands->went->wasHeldByOtherWork()) {
            return;
        }

        $this->tellIt($sent);
    }

    /** Put a yes the stack refused because what it was given for has moved back as a question. */
    abstract protected function offerAgain(AgreedTo $sent): void;

    /** Let go of the start's subscription as the screen stops, keeping the last line. */
    protected function letGoOfWhatElseItHears(): void
    {
        $this->hearingTheStart->letGo();
    }

    /**
     * Take the stack's newest line for what a running start waits on, on a wake whose last report said it runs.
     *
     * Only for a start or a restart this screen sent: that is when the stack
     * says what the wait is for, and anything else lets go. A stream that
     * could not be heard keeps the last line, because the verb's own report
     * is what says whether anything stood in the way.
     */
    protected function hearWhatTheySaid(): void
    {
        $stack = $this->stack();
        $sent = $this->sent;
        $job = $this->theRunningJob();
        if (! $this->lastSaidItRuns()) {
            return;
        }

        $this->theWakeAsks();

        if (! $job instanceof Job || ! $sent instanceof AgreedTo || ! $sent->doing()->bringsSomethingUp()) {
            $this->hearingTheStart->letGo();

            return;
        }

        $kept = new AStartLineAsShown($this->waitsOn);

        $this->waitsOn = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AStartLineAsShown => $this->hearingTheStart->whatItWaitsOn($stack, $session, $job)->either(
                saying: static fn(string $line): AStartLineAsShown => new AStartLineAsShown($line),
                nothingNew: static fn(): AStartLineAsShown => $kept,
                met: function (Obstacle $why) use ($kept, $stack): AStartLineAsShown {
                    $this->letGoOfTheSession($why, $stack);

                    return $kept;
                },
            ),
            notHeld: static fn(): AStartLineAsShown => $kept,
        )->said;
    }

    /** Let go of the start's subscription once the verb no longer runs. */
    protected function letGoUnlessAnotherRuns(): void
    {
        if (! $this->awaitsAnOutcome()) {
            $this->hearingTheStart->letGo();
        }
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowTheVerbWent
    {
        $sent = $this->sent;

        if (! $sent instanceof AgreedTo) {
            return new HowAVerbEndedReads()->notAsked();
        }

        return $this->supervising->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->running(),
            done: static fn(WhatTheVerbCameTo $report): HowTheVerbWent => new HowAVerbEndedReads()->done($report, $sent->doing()),
            ended: static fn(?WhyTheWorkEnded $why): HowTheVerbWent => new HowAVerbEndedReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowAVerbEndedReads()->met(...)),
            // Refused and offered again, as a repair is: nothing was done,
            // the yes was given for what has since moved, so what the stack
            // said is kept and the verb is put back as a question.
            moved: function (ARefusalInItsWords $why) use ($sent): HowTheVerbWent {
                $this->forget();
                $this->movedOn = new HowARefusalReads()->inItsWords($why);
                $this->offerAgain($sent);

                return new HowAVerbEndedReads()->notAsked();
            },
        );
    }

    protected function whileNoJobIsFollowed(): HowTheVerbWent
    {
        return new HowAVerbEndedReads()->notAsked();
    }

    protected function withNoSessionHeld(): HowTheVerbWent
    {
        return new HowAVerbEndedReads()->signedOut();
    }

    /** Whether this frame may take another reading of the stack, which taking it promises. */
    abstract private function mayReadItsStack(): bool;

    /**
     * Send the verb agreed to, and follow what it started.
     *
     * Just sent, it is running, and the cadence asks after it from there. A
     * refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though the verb were running.
     */
    private function tellIt(AgreedTo $agreed): void
    {
        $stack = $this->stack();
        $this->forget();
        $this->sent = $agreed;

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheVerbWent => $this->supervising->told($stack, $session, $agreed)->either(
                started: function (Job $job): HowTheVerbWent {
                    $this->follow($job);

                    return new HowAVerbEndedReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowAVerbEndedReads()->met(...)),
            ),
            notHeld: static fn(): HowTheVerbWent => new HowAVerbEndedReads()->signedOut(),
        ));
    }
}
