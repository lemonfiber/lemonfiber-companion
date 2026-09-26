<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAWalkthroughReads;
use Modules\Operator\Internal\ShowsWhatItsWordsMean;
use Modules\Operator\Internal\ViewModels\TheWalkthroughAsRecorded;
use Modules\Operator\Internal\ViewModels\WhatTheWalkthroughTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Watching one thing arrive: a walkthrough of fetching it, narrated end to end.
 *
 * Before one is started it shows the road a walk takes, each step in the
 * glossary's words. Started from here with a title, or with nothing so the
 * stack picks something likely to work. While it runs the handle is asked after at a stated cadence;
 * once it finishes, what it said is drawn whole, as the record of it, with
 * where it stopped and why, what the import did, and what to do next.
 *
 * **Already here is an outcome.** Something the stack already has is said to
 * be here, first and in its own words, and never drawn as a search that
 * matched nothing.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WatchingOneArrive extends NativeComponent
{
    use LetsGoOfARefusedSession;
    use ShowsWhatItsWordsMean;

    /** What is typed into the box, walked only when asked to. Public for {@see WhatThisServiceSaid::$looking}'s reason. */
    public string $looking = '';

    /** The handle starting the walkthrough answered. Public for {@see HowCurrentThisStackIs::$answered}'s reason. */
    public ?string $took = null;

    /** What became of it, once this frame has asked. */
    public ?WhatTheWalkthroughTurnedOutToBe $answered = null;

    public function __construct(
        private readonly WalkingThrough $walking,
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /** The stack this screen is about, read from the route on every frame, for {@see WhatStoppedComingIn::stack()}'s reason. */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
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
     * Ask after the walkthrough again while the stack is walking it.
     *
     * It does nothing unless the walk is running, which is what keeps this
     * from being polling: a finished record answers the same however often it
     * is read. The interval is {@see HowOften}'s constant, which
     * {@see cadence()} states on the screen.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->answered = null;
        }
    }

    /** How often this screen asks after a walkthrough running, as the screen states it. */
    public function cadence(): HowOften
    {
        return HowOften::WhileWorkRuns;
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

    /**
     * Start a walkthrough, and hold what to follow it by.
     *
     * Just started, it is running, and the cadence asks after it from there. A
     * refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though it were running.
     */
    private function start(WhatToWalk $asked): void
    {
        $stack = $this->stack();
        $this->took = null;

        $this->answered = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheWalkthroughTurnedOutToBe => $this->walking->walk($stack, $session, $asked)->either(
                started: function (Job $job): WhatTheWalkthroughTurnedOutToBe {
                    $this->took = $job->shown();

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
                ended: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->ended(),
                met: fn(Obstacle $why): WhatTheWalkthroughTurnedOutToBe => $this->refused($why, $stack),
            ),
            notHeld: static fn(): WhatTheWalkthroughTurnedOutToBe => new HowAWalkthroughReads()->signedOut(),
        );
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
