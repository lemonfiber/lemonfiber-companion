<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WorkLeftRunning;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\HearsWhereTheWalkIs;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAWalkthroughReads;
use Modules\Operator\Internal\ShowsWhatItsWordsMean;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\TheWalkthroughAsRecorded;
use Modules\Operator\Internal\ViewModels\WhatTheWalkthroughTurnedOutToBe;
use Modules\Operator\Internal\WhatTheWalkIsFollowedWith;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhetherItIsHeld;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Watching one thing arrive: a walkthrough of fetching it, narrated end to end.
 *
 * Before one is started it shows the road a walk takes, each step in the
 * glossary's words. Started from here with a title, or with nothing so the
 * stack picks something likely to work. While it runs the handle is asked after at a declared cadence,
 * and the stage it is at is taken on the same wakes from the stack's event
 * stream, {@see HearsWhereTheWalkIs}; once it finishes, what it said is drawn
 * whole, as the record of it, with where it stopped and why, what the import
 * did, and what to do next.
 *
 * **Already here is an outcome.** Something the stack already has is said to
 * be here, first and in its own words, and never drawn as a search that
 * matched nothing.
 *
 * **Leaving does not stop a walk.** The stack carries on with it whether or not
 * anybody is watching, and the screen says so while it runs. What leaving does
 * drop is the screen, so the handle is also kept on the device
 * ({@see WorkLeftRunning}), and the screen opened again follows it: returning
 * shows where the walk got to rather than offering to start it over.
 *
 * **A finished record stays until another walk starts.** Coming back to a walk
 * that finished while nobody was looking is the case the record exists for, so
 * the handle is kept past the end and the record drawn again on each return.
 * Starting another walk lets go of it, and so does the stack no longer knowing
 * it: an outcome the stack cannot give is said once, on the screen that asked,
 * and the next opening offers a new walk instead of saying it again.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WatchingOneArrive extends NativeComponent
{
    use HearsWhereTheWalkIs;
    use LetsGoOfARefusedSession;
    use ShowsWhatItsWordsMean;
    use FindsItsWayAround;

    /** What is typed into the box, walked only when asked to. Public for {@see WhatThisServiceSaid::$looking}'s reason. */
    public string $looking = '';

    /** The handle starting the walkthrough answered. Public for {@see HowCurrentThisStackIs::$answered}'s reason. */
    public ?string $took = null;

    /** What became of it, once this frame has asked. */
    public ?WhatTheWalkthroughTurnedOutToBe $answered = null;

    /**
     * Whether coming back to this screen will find the walk started here.
     *
     * Only ever false where this device would not keep the handle of a walk it
     * just started, which the screen says while that walk runs. Public for
     * {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public bool $willBeFoundAgain = true;

    public function __construct(
        private readonly WalkingThrough $walking,
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        private readonly WorkLeftRunning $leftRunning,
        private readonly HearingTheWalk $hearingTheWalk,
        private readonly Clock $clock,
        private readonly Capture $capture,
    ) {}

    /**
     * Pick up the walk this device left running on this stack, where there is one.
     *
     * A screen opened again is a new screen, holding nothing of the one that was
     * left. The handle kept when the walk started is what it follows instead.
     * Read from the device rather than the stack, so the first frame waits on
     * nothing.
     */
    public function mount(): void
    {
        // A handle is never blank, so an empty one can only be the arm that
        // found nothing.
        $left = $this->leftRunning->whatWasLeft($this->stack()->id(), KindOfWork::Walkthrough)->either(
            job: static fn(Job $job): AsText => AsText::of($job->shown()),
            nothing: static fn(): AsText => AsText::nothing(),
        )->said;

        $this->took = $left === '' ? null : $left;
    }

    /** The stack this screen is about, read from the route on every frame, for {@see WhatStoppedComingIn::stack()}'s reason. */
    public function stack(): Stack
    {
        return $this->around->stackNamed($this->param('stack'));
    }

    /** Walk through what was typed, or, with nothing typed, something the stack picks. */
    public function walk(): void
    {
        $asked = WhatToWalk::called($this->looking);
        $this->looking = '';

        $this->start($asked);
    }

    /**
     * Walk through one of the things the last walkthrough suggested, by its place in that list.
     *
     * A place the list does not have starts nothing.
     */
    public function walkSuggested(string $place): void
    {
        $record = $this->answer()->record;

        if (! $record instanceof TheWalkthroughAsRecorded) {
            return;
        }

        $chosen = null;

        foreach ($record->suggestions as $at => $suggestion) {
            if ((string) $at === $place) {
                $chosen = WhatToWalk::called($suggestion);
            }
        }

        if ($chosen instanceof WhatToWalk) {
            $this->start($chosen);
        }
    }

    /** Ask the machine again. */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Ask after the walkthrough again while the stack is walking it, and take the stage it said.
     *
     * It asks nothing unless the walk is running, which is what keeps this
     * from being polling: a finished record answers the same however often it
     * is read, and the stream is let go of once the walk is over. Taking the
     * stage sends nothing to the stack. The interval is {@see HowOften}'s
     * constant.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if (! $this->answer()->isWorking) {
            $this->letGoOfAWalkThatIsOver();

            return;
        }

        $this->answered = null;
        $this->listenToTheWalk();
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::watching-one-arrive');
    }

    /** What became of the walkthrough started here, asked once per frame. */
    public function answer(): WhatTheWalkthroughTurnedOutToBe
    {
        return $this->answered ??= $this->followed();
    }

    /** Where this screen's words are explained from. */
    protected function explaining(): Explaining
    {
        return $this->explaining;
    }

    protected function followsTheWalkWith(): WhatTheWalkIsFollowedWith
    {
        return new WhatTheWalkIsFollowedWith($this->hearingTheWalk, $this->clock, $this->capture);
    }

    /**
     * Start a walkthrough, and hold what to follow it by, here and on the device.
     *
     * The stream is opened first, so the first step the walk says is one this
     * screen hears. Just started, it is running, and the cadence asks after it
     * from there. A refusal is kept as what became of it, so the screen says
     * what stood in the way rather than carrying on as though it were running.
     *
     * The walk before it is let go of first, whatever becomes of the start:
     * asking for another walk is moving on from the last, and a start that
     * failed leaves nothing to come back to rather than an older walk the
     * operator had already turned from.
     */
    private function start(WhatToWalk $asked): void
    {
        $stack = $this->stack();
        $this->took = null;
        $this->leftRunning->forget($stack->id(), KindOfWork::Walkthrough);
        $this->listenToANewWalk();

        $this->answered = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheWalkthroughTurnedOutToBe => $this->walking->walk($stack, $session, $asked)->either(
                started: function (Job $job) use ($stack): WhatTheWalkthroughTurnedOutToBe {
                    $this->took = $job->shown();
                    $this->willBeFoundAgain = $this->leftRunning->remember($stack->id(), KindOfWork::Walkthrough, $job)->either(
                        job: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIs(),
                        nothing: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIsNot(),
                    )->held;

                    return new HowAWalkthroughReads()->running();
                },
                met: fn(Obstacle $why): WhatTheWalkthroughTurnedOutToBe => $this->refused($why, $stack),
            ),
            notHeld: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->signedOut(),
        );
    }

    /** Ask the stack what became of the walkthrough started, or say that none was. */
    private function followed(): WhatTheWalkthroughTurnedOutToBe
    {
        $took = $this->took;

        if ($took === null) {
            return $this->beforeAnyWalk();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheWalkthroughTurnedOutToBe => $this->walking->whatBecameOf($stack, $session, Job::named($took))->either(
                stillRunning: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->running(),
                done: static fn(AWalkthrough $report): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->done($report),
                ended: fn(): WhatTheWalkthroughTurnedOutToBe => $this->endedWithNoOutcome($stack),
                met: fn(Obstacle $why): WhatTheWalkthroughTurnedOutToBe => $this->refused($why, $stack),
            ),
            notHeld: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->signedOut(),
        );
    }

    /**
     * The stack has no outcome for the walk any more, so there is nothing left to come back to.
     *
     * Said on this screen, and let go of on the device, so the next opening
     * offers a new walk rather than the same missing outcome.
     */
    private function endedWithNoOutcome(Stack $stack): WhatTheWalkthroughTurnedOutToBe
    {
        $this->leftRunning->forget($stack->id(), KindOfWork::Walkthrough);

        return new HowAWalkthroughReads()->ended();
    }

    /**
     * Nothing started yet: the road a walk takes, in the glossary's words.
     *
     * The glossary is what this frame asks the stack for, and it is held, so
     * the steps drawn are explained without a second asking. A glossary that
     * could not be had is what stood in the way, drawn as such, because a
     * screen that asked nothing could not say whether the machine is there.
     */
    private function beforeAnyWalk(): WhatTheWalkthroughTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheWalkthroughTurnedOutToBe => $this->explaining->glossaryOn($stack, $session)->either(
                found: function (TheGlossary $words): WhatTheWalkthroughTurnedOutToBe {
                    $this->glossary = $words;

                    return new HowAWalkthroughReads()->notStarted();
                },
                met: fn(Obstacle $why): WhatTheWalkthroughTurnedOutToBe => $this->refused($why, $stack),
            ),
            notHeld: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->signedOut(),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): WhatTheWalkthroughTurnedOutToBe
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowAWalkthroughReads()->met($why);
    }
}
