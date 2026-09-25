<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\Presenters\HowTheLastUpdateReads;
use Modules\Operator\Internal\ViewModels\HowTheLastUpdateWent;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that takes an update and follows it to the stack's report.
 *
 * Taking an update answers a handle, and the stack's report of how each
 * service took it arrives only through that handle: a plain reading of where
 * the stack stands leaves it out. So the handle is held, asked after while the
 * update runs, and the report drawn once it finishes.
 *
 * **It reads the using screen's own `$keeping` and `$storage`**, for the
 * reason {@see LetsGoOfARefusedSession} gives, and lets go of a session the
 * stack refused through that trait, which the screen also uses.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsTheUpdateItTook
{
    /** The handle taking the update answered. Public for {@see Screens\HowCurrentThisStackIs::$answered}'s reason. */
    public ?string $took = null;

    /** What became of it, once this frame has asked. */
    public ?HowTheLastUpdateWent $lastUpdated = null;

    /** What became of the update taken here, or that none was. */
    public function lastUpdate(): HowTheLastUpdateWent
    {
        return $this->lastUpdated ??= $this->followed();
    }

    /**
     * Ask after the update again while the stack is carrying it out.
     *
     * It does nothing unless the update is running, which is what keeps this
     * from being polling: a finished report answers the
     * same thing however often it is read. The interval is
     * {@see HowOften}'s constant, which {@see cadence()} states on the screen.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->lastUpdate()->isWorking) {
            $this->lastUpdated = null;
        }
    }

    /** How often this screen asks after an update running, as the screen states it. */
    public function cadence(): HowOften
    {
        return HowOften::WhileWorkRuns;
    }

    abstract public function stack(): Stack;

    /**
     * Take the update agreed to, and hold what to follow it by.
     *
     * Just taken, it is running, and the cadence asks after it from there. A
     * refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though the update were running.
     */
    private function takeIt(TakingAnUpdate $taking): void
    {
        $stack = $this->stack();
        $this->took = null;

        $this->lastUpdated = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheLastUpdateWent => $this->keeping->take($stack, $session, $taking)->either(
                started: function (Job $job): HowTheLastUpdateWent {
                    $this->took = $job->shown();

                    return new HowTheLastUpdateReads()->running();
                },
                met: fn(Obstacle $why): HowTheLastUpdateWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->signedOut(),
        );
    }

    /** Ask the stack what became of the update taken, or say that none was. */
    private function followed(): HowTheLastUpdateWent
    {
        $took = $this->took;

        if ($took === null) {
            return new HowTheLastUpdateReads()->notTaken();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheLastUpdateWent => $this->keeping->whatBecameOf($stack, $session, Job::named($took))->either(
                stillRunning: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->running(),
                done: static fn(Upkeep $report): HowTheLastUpdateWent => new HowTheLastUpdateReads()->done($report),
                ended: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->ended(),
                met: fn(Obstacle $why): HowTheLastUpdateWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheLastUpdateWent => new HowTheLastUpdateReads()->signedOut(),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): HowTheLastUpdateWent
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowTheLastUpdateReads()->met($why);
    }
}
