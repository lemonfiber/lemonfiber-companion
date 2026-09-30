<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheReset;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAResetReads;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Putting the stack's configuration back to lemonfiber's own, previewed first
 * and carried out only on a yes.
 *
 * **The preview comes first, and is labelled as one.** The stack compares the
 * operator's files and connections with lemonfiber's and writes nothing. The
 * screen draws every file that would go back, with the lines that change —
 * the operator's marked `-`, lemonfiber's `+` — and every connection that
 * would go with them. A preview that would change nothing says so and offers
 * nothing to agree to.
 *
 * **Only the preview can be agreed to.** The yes is an {@see AResetAgreed},
 * which only a preview that would revert something produces.
 *
 * **Both are work the stack names,** followed on a declared cadence while they
 * run. The report after the yes says what went back and which connections
 * went with it, in the past tense only where the stack says it was carried out.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class PuttingTheConfigurationBack extends NativeComponent
{
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What the preview came to, once the frame has asked.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?AResetAsShown $answered = null;

    /** What became of the yes, once this frame has asked. */
    public ?AResetAsShown $carriedOut = null;

    /**
     * The preview, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * built from the preview itself.
     */
    public ?TheReset $previewed = null;

    /**
     * The handle the stack answered with: the preview's, then the yes's.
     *
     * Kept so asking again reads the same job rather than starting a second.
     * Not shown and never kept past this screen.
     */
    public ?string $handle = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    public function __construct(
        private readonly ResettingTheConfiguration $resetting,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
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

    public function render(): View
    {
        return view('operator::putting-the-configuration-back');
    }

    /** The reading a frame draws: what the yes came to once it was given, and the preview until then. */
    public function answer(): AResetAsShown
    {
        return $this->agreed ? $this->done() : $this->preview();
    }

    /** What putting the configuration back would revert, asked once per frame. */
    public function preview(): AResetAsShown
    {
        return $this->answered ??= $this->ask();
    }

    /** Whether the operator has agreed to the preview. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): AResetAsShown
    {
        return $this->carriedOut ??= $this->followed();
    }

    /**
     * Put the configuration back, as previewed.
     *
     * Silent where no preview is held that would revert something: there is
     * nothing to agree to.
     */
    public function agree(): void
    {
        $previewed = $this->previewed;

        if (! $previewed instanceof TheReset || ! $previewed->mayBeAgreedTo()) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;

        $this->carriedOut = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AResetAsShown => $this->resetting->revert($stack, $session, AResetAgreed::to($previewed))->either(
                started: function (Job $job): AResetAsShown {
                    $this->handle = $job->shown();

                    return HowAResetReads::afterTheYes()->running();
                },
                met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, HowAResetReads::afterTheYes()),
            ),
            notHeld: static fn(): AResetAsShown => HowAResetReads::afterTheYes()->signedOut(),
        );
    }

    /**
     * Ask again, because the operator said so.
     *
     * The handle is kept only while its job still runs, on either side of the
     * yes, because reading it again is the only way to learn it has finished.
     * Anything else asks for the preview afresh: a finished preview can have
     * moved on, a refused yes put nothing back, and after a yes that finished
     * or that the stack no longer knows, a fresh preview is what says what
     * still differs.
     */
    public function again(): void
    {
        $reading = $this->agreed ? $this->carriedOut : $this->answered;
        $working = $reading?->isWorking === true;
        $this->carriedOut = null;

        if ($this->agreed && $working) {
            return;
        }

        if (! $working) {
            $this->handle = null;
        }

        $this->agreed = false;
        $this->previewed = null;
        $this->answered = null;
    }

    /**
     * Ask after the preview or the yes while the stack is at it.
     *
     * It does nothing unless one is running, so a finished report is not read
     * over and over. The interval is {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->again();
        }
    }

    /** Whether the stack is at it right now. */
    public function isWorking(): bool
    {
        return $this->answer()->isWorking;
    }

    /** Resume the session and ask for the preview. */
    private function ask(): AResetAsShown
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AResetAsShown => $this->read($stack, $session),
            notHeld: static fn(): AResetAsShown => HowAResetReads::beforeTheYes()->signedOut(),
        );
    }

    /**
     * Read the preview's handle, asking for the preview first where there is none.
     *
     * A handle already in hand is read, and only the absence of one starts
     * anything, so asking again is a second read rather than a second piece of
     * work on somebody's machine.
     */
    private function read(Stack $stack, Session $session): AResetAsShown
    {
        $held = $this->handle;

        if (is_string($held)) {
            return $this->previewedAs($this->resetting->whatBecameOf($stack, $session, Job::named($held)), $stack);
        }

        return $this->resetting->wouldRevert($stack, $session)->either(
            started: function (Job $job) use ($stack, $session): AResetAsShown {
                $this->handle = $job->shown();

                return $this->previewedAs($this->resetting->whatBecameOf($stack, $session, $job), $stack);
            },
            met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, HowAResetReads::beforeTheYes()),
        );
    }

    /** What became of the preview, held to agree against where the stack reported one. */
    private function previewedAs(HowTheResetIsGoing $going, Stack $stack): AResetAsShown
    {
        $reads = HowAResetReads::beforeTheYes();

        return $going->either(
            stillRunning: $reads->running(...),
            done: function (TheReset $reset) use ($reads): AResetAsShown {
                $this->previewed = $reset;

                return $reads->reported($reset);
            },
            refused: $reads->refused(...),
            ended: $reads->ended(...),
            met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, $reads),
        );
    }

    /** Ask the stack what became of the yes. */
    private function followed(): AResetAsShown
    {
        $reads = HowAResetReads::afterTheYes();
        $held = $this->handle;

        if (! is_string($held)) {
            return $reads->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AResetAsShown => $this->resetting->whatBecameOf($stack, $session, Job::named($held))->either(
                stillRunning: $reads->running(...),
                done: $reads->reported(...),
                refused: $reads->refused(...),
                ended: $reads->ended(...),
                met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, $reads),
            ),
            notHeld: $reads->signedOut(...),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refusedBy(Obstacle $why, Stack $stack, HowAResetReads $reads): AResetAsShown
    {
        $this->letGoOfTheSession($why, $stack);

        return $reads->met($why);
    }
}
