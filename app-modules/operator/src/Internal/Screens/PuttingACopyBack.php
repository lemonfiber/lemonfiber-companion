<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowPuttingItBackReads;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\HowPuttingItBackWent;
use Modules\Operator\Internal\ViewModels\WhatPuttingItBackWouldShow;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Putting one copy back, rehearsed first and carried out only on a yes.
 *
 * **The rehearsal comes first, and is labelled as one.** The stack reads the
 * copy's own account of itself and changes nothing: what it covers, what it
 * holds, which lemonfiber wrote it and when, whether it goes back a version,
 * and where its data would land if not where it came from. Nothing on that
 * frame is worded as having happened.
 *
 * **Only the listing can be agreed to.** The yes quotes the listing by the
 * name the stack gave it, so what is put back is what was shown; a stack that
 * would not list the copy offers nothing to agree to, and the screen offers
 * nothing.
 *
 * **Putting it back answers a handle,** followed on a declared cadence while it
 * runs, as taking an update is. The report says what was restored and where
 * the data went.
 *
 * **A copy the stack will not restore is its answer, not a fault.** Refused
 * at the listing or while being put back, it is drawn in the stack's words,
 * apart from a stack that could not be reached, and asking again is not
 * offered, because the same question is answered the same way. A refused
 * listing offers the copies; a refused yes offers reading what putting it back
 * would do now, which is a new question.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class PuttingACopyBack extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What the rehearsal came to, once the frame has asked.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?WhatPuttingItBackWouldShow $answered = null;

    /**
     * The listing, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * sent against the listing itself.
     */
    public ?WhatPuttingItBackWouldDo $listed = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    /** The handle agreeing answered. Not shown and never kept past this screen. */
    public ?string $handle = null;

    /** What became of the yes, once this frame has asked. */
    public ?HowPuttingItBackWent $carriedOut = null;

    public function __construct(
        private readonly PuttingBack $puttingBack,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame, for
     * {@see WhatThisMachineKeepsHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        return $this->around->stackNamed($this->param('stack'));
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->isWorking();
    }

    public function render(): View
    {
        return view('operator::putting-a-copy-back');
    }

    /** The copy this screen is about, by the name the route carries. */
    public function copyNamed(): string
    {
        $named = $this->param('service');

        return is_string($named) ? $named : '';
    }

    /** What putting the copy back would do, asked once per frame. */
    public function answer(): WhatPuttingItBackWouldShow
    {
        return $this->answered ??= $this->rehearse();
    }

    /** Whether the operator has agreed to the listing. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowPuttingItBackWent
    {
        return $this->carriedOut ??= $this->followed();
    }

    /**
     * Put the copy back as it was listed.
     *
     * Silent where no listing is held, which is a frame that has not read one
     * or a stack that would not list the copy: there is nothing to agree to.
     */
    public function agree(): void
    {
        $listed = $this->listed;

        if (! $listed instanceof WhatPuttingItBackWouldDo) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;
        $this->handle = null;

        $this->carriedOut = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPuttingItBackWent => $this->puttingBack->putBack($stack, $session, $listed)->either(
                started: function (Job $job): HowPuttingItBackWent {
                    $this->handle = $job->shown();

                    return new HowPuttingItBackReads()->running();
                },
                met: fn(Obstacle $why): HowPuttingItBackWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowPuttingItBackWent => new HowPuttingItBackReads()->signedOut(),
        );
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same handle. Otherwise
     * — before a yes, or after one the stack refused — it asks for the
     * listing afresh, since a listing can move on and a refused yes put
     * nothing back.
     */
    public function again(): void
    {
        $this->carriedOut = null;

        if ($this->agreed && is_string($this->handle)) {
            return;
        }

        $this->agreed = false;
        $this->listed = null;
        $this->answered = null;
    }

    /**
     * Read what putting the copy back would do now, leaving a yes the stack
     * refused behind.
     *
     * The listing is asked for afresh and nothing is agreed to, so a yes is
     * only ever sent against a listing the operator has just read.
     */
    public function lookAgain(): void
    {
        $this->agreed = false;
        $this->handle = null;
        $this->carriedOut = null;
        $this->listed = null;
        $this->answered = null;
    }

    /**
     * Ask after the copy being put back while the stack is doing it.
     *
     * It does nothing unless that is running, so a rehearsal and a finished
     * report are not read over and over. The interval is {@see HowOftenAScreenLooks}'s
     * constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->isWorking()) {
            $this->carriedOut = null;
        }
    }

    /** Whether the stack is putting the copy back right now. */
    public function isWorking(): bool
    {
        return $this->agreed && $this->done()->isWorking;
    }

    /** Resume the session and ask what putting the copy back would do. */
    private function rehearse(): WhatPuttingItBackWouldShow
    {
        $named = $this->copyNamed();

        if (trim($named) === '') {
            return new HowPuttingItBackReads()->namesNoCopy();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatPuttingItBackWouldShow => $this->listing($stack, $session, ACopy::named($named)),
            notHeld: static fn(): WhatPuttingItBackWouldShow => new HowPuttingItBackReads()->notAsked(),
        );
    }

    /** The stack's listing, held to agree against, or what the operator met instead. */
    private function listing(Stack $stack, Session $session, ACopy $copy): WhatPuttingItBackWouldShow
    {
        return $this->puttingBack->rehearse($stack, $session, $copy)->either(
            listed: function (WhatPuttingItBackWouldDo $listing): WhatPuttingItBackWouldShow {
                $this->listed = $listing;

                return new HowPuttingItBackReads()->listing($listing);
            },
            refused: static fn(ARefusalInItsWords $why): WhatPuttingItBackWouldShow => new HowPuttingItBackReads()->refusedToList($why),
            met: function (Obstacle $why) use ($stack): WhatPuttingItBackWouldShow {
                $this->letGoOfTheSession($why, $stack);

                return new HowPuttingItBackReads()->notListed($why);
            },
        );
    }

    /** Ask the stack what became of the yes. */
    private function followed(): HowPuttingItBackWent
    {
        $held = $this->handle;

        if (! is_string($held)) {
            return new HowPuttingItBackReads()->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPuttingItBackWent => $this->puttingBack->whatBecameOf($stack, $session, Job::named($held))->either(
                stillRunning: static fn(): HowPuttingItBackWent => new HowPuttingItBackReads()->running(),
                done: static fn(ACopyPutBack $report): HowPuttingItBackWent => new HowPuttingItBackReads()->done($report),
                refused: static fn(ARefusalInItsWords $why): HowPuttingItBackWent => new HowPuttingItBackReads()->refused($why),
                ended: static fn(): HowPuttingItBackWent => new HowPuttingItBackReads()->ended(),
                met: fn(Obstacle $why): HowPuttingItBackWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowPuttingItBackWent => new HowPuttingItBackReads()->signedOut(),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): HowPuttingItBackWent
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowPuttingItBackReads()->met($why);
    }
}
