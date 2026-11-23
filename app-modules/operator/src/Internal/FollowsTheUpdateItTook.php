<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\Presenters\HowTheLastUpdateReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\HowTheLastUpdateWent;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that takes an update and follows it to the stack's report.
 *
 * Taking an update answers a job, and the stack's report of how each service
 * took it arrives only through that job: a plain reading of where the stack
 * stands leaves it out. So the job is followed by {@see FollowsOneJobAtATime}, which
 * shows what it says while the update runs, and the report is drawn once it
 * finishes.
 *
 * **It reads the using screen's own `$keeping`, `$storage` and `$hearingTheJobs`**, for the
 * reason {@see LetsGoOfARefusedSession} gives, and lets go of a session the
 * stack refused through that trait, which the screen also uses.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsTheUpdateItTook
{
    /** @use FollowsOneJobAtATime<HowTheLastUpdateWent> */
    use FollowsOneJobAtATime;

    /** The update last sent, so a request other work held can be sent again as it was agreed to. */
    public ?TakingAnUpdate $taken = null;

    /** What the stack said where it refused the last update because what it would apply has moved, or nothing. */
    public ?ARefusalAsShown $movedOn = null;

    /** What became of the update taken here, or that none was. */
    public function lastUpdate(): HowTheLastUpdateWent
    {
        return $this->whatBecameOfTheJob();
    }

    /** Read again what the stack would do, on the next frame. */
    abstract public function again(): void;

    /**
     * Take the update that was agreed to again, where other work held the stack.
     *
     * Only then, for {@see FollowsWhatTheVerbCameTo::tryAgain()}'s reason.
     */
    public function tryAgain(): void
    {
        $taken = $this->taken;

        if (! $taken instanceof TakingAnUpdate || ! $this->lastUpdate()->stands->went->wasHeldByOtherWork()) {
            return;
        }

        $this->takeIt($taken);
    }

    protected function whileNoJobIsFollowed(): HowTheLastUpdateWent
    {
        return new HowTheLastUpdateReads()->notTaken();
    }

    protected function withNoSessionHeld(): HowTheLastUpdateWent
    {
        return new HowTheLastUpdateReads()->signedOut();
    }

    /** Ask the stack what became of the update taken. */
    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowTheLastUpdateWent
    {
        return $this->keeping->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->running(),
            done: static fn(Upkeep $report): HowTheLastUpdateWent => new HowTheLastUpdateReads()->done($report),
            ended: static fn(?WhyTheWorkEnded $why): HowTheLastUpdateWent => new HowTheLastUpdateReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowTheLastUpdateReads()->met(...)),
            // Refused and offered again, as a repair is: nothing was
            // applied, the yes was given for what has since moved, so what
            // the stack said is kept and what it would do now is read
            // again for a fresh yes.
            moved: function (ARefusalInItsWords $why): HowTheLastUpdateWent {
                $this->forget();
                $this->taken = null;
                $this->movedOn = new HowARefusalReads()->inItsWords($why);
                $this->again();

                return new HowTheLastUpdateReads()->notTaken();
            },
        );
    }

    /**
     * Take the update agreed to, and follow the job it answers with.
     *
     * Just taken, it is running, and the cadence asks after it from there. A
     * refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though the update were running.
     */
    private function takeIt(TakingAnUpdate $taking): void
    {
        $stack = $this->stack();
        $this->forget();
        $this->movedOn = null;
        $this->taken = $taking;

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheLastUpdateWent => $this->keeping->take($stack, $session, $taking)->either(
                started: function (Job $job): HowTheLastUpdateWent {
                    $this->follow($job);

                    return new HowTheLastUpdateReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowTheLastUpdateReads()->met(...)),
            ),
            notHeld: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->signedOut(),
        ));
    }

}
