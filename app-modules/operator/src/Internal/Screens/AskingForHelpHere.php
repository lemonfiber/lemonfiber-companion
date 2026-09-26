<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\ChoosesWhatABundleHolds;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowABundleReads;
use Modules\Operator\Internal\ViewModels\ABundleAsShown;
use Modules\Operator\Internal\ViewModels\HowTheBundleWent;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Asking for help: a support bundle, described, and only then written.
 *
 * **It opens on a description.** The stack is asked what a bundle made the
 * careful way would hold — the usual log window, filenames replaced, nothing
 * revealed — which writes nothing, so the first thing the operator reads is
 * what the stack would put in the file. The choices are one tap away, in
 * {@see ChoosesWhatABundleHolds}: the log window, whether media filenames are
 * shown, and each setting revealed on its own yes. Describing again is what
 * leaves them.
 *
 * **Writing is a second yes, on a description.** The stack answers with what
 * the bundle would hold, how large it would be and where it would go, and
 * only then is writing offered. What is written is what was described: the
 * choices are held with the description and sent again as they were.
 *
 * **Both answer a handle,** followed on a stated cadence while the stack
 * gathers. A bundle the stack refused is its answer, drawn as a refusal in its
 * own words and never as a fault to try again.
 *
 * This screen adds nothing to a bundle and sends it nowhere. The file stays on
 * the stack's machine; what is drawn is only what the stack answered with.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class AskingForHelpHere extends NativeComponent
{
    use ChoosesWhatABundleHolds;
    use LetsGoOfARefusedSession;

    /**
     * The bundle sent, with every choice as it was made.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason, and held
     * so the bundle written is the one described.
     */
    public ?ABundleAsked $asked = null;

    /** The handle asking answered. Not shown and never kept past this screen. */
    public ?string $handle = null;

    /** What became of it, once this frame has asked. */
    public ?HowTheBundleWent $went = null;

    /** Whether the operator is changing what goes in, which asks the stack nothing until they describe it. */
    public bool $choosing = false;

    public function __construct(
        private readonly AskingForHelp $helping,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame, for
     * {@see WhatThisMachineKeepsHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::asking-for-help-here');
    }

    /** Ask the stack to describe the bundle chosen, which writes nothing. */
    public function describe(): void
    {
        $this->choosing = false;
        $this->went = $this->send($this->chosen());
    }

    /**
     * Write the bundle that was described, exactly as it was described.
     *
     * Silent unless a description is what the stack last answered with: the yes
     * is to a description the operator has read, and to nothing else.
     */
    public function write(): void
    {
        $asked = $this->asked;

        if (! $asked instanceof ABundleAsked || $asked->writes() || ! $this->answer()->bundle instanceof ABundleAsShown) {
            return;
        }

        $this->went = $this->send($asked->written());
    }

    /** Go back to the choices, keeping them, and forget the bundle followed. */
    public function startOver(): void
    {
        $this->choosing = true;
        $this->asked = null;
        $this->handle = null;
        $this->went = null;
    }

    /** Ask the stack again what became of the bundle. */
    public function again(): void
    {
        $this->went = null;
    }

    /** What became of the bundle asked for here, asked once per frame. */
    public function answer(): HowTheBundleWent
    {
        return $this->went ??= $this->followed();
    }

    /**
     * Ask after the bundle again while the stack is gathering it.
     *
     * It does nothing unless that is running, so a finished bundle is not read
     * over and over. The interval is {@see HowOften}'s constant, which
     * {@see cadence()} states on the screen.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->went = null;
        }
    }

    /** How often this screen asks after a bundle being gathered, as the screen states it. */
    public function cadence(): HowOften
    {
        return HowOften::WhileWorkRuns;
    }

    /**
     * Send the bundle asked for, and hold what to follow it by.
     *
     * An obstacle is what became of it, so the screen says what stood in the
     * way rather than carrying on as though a bundle were being gathered.
     */
    private function send(ABundleAsked $asked): HowTheBundleWent
    {
        $stack = $this->stack();
        $this->asked = $asked;
        $this->handle = null;

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheBundleWent => $this->helping->ask($stack, $session, $asked)->either(
                started: function (Job $job): HowTheBundleWent {
                    $this->handle = $job->shown();

                    return new HowABundleReads()->running();
                },
                met: fn(Obstacle $why): HowTheBundleWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheBundleWent => new HowABundleReads()->signedOut(),
        );
    }

    /**
     * Ask the stack what became of the bundle asked for.
     *
     * Where nothing is being followed — the screen has just opened, or asking
     * met an obstacle — the choices as they stand are described, which writes
     * nothing. A write that met an obstacle is never sent again from here: the
     * operator reads the description and agrees again when they choose to.
     */
    private function followed(): HowTheBundleWent
    {
        if ($this->choosing) {
            return new HowABundleReads()->notAsked();
        }

        $handle = $this->handle;

        if ($handle === null) {
            return $this->send($this->chosen());
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheBundleWent => $this->helping->whatBecameOf($stack, $session, Job::named($handle))->either(
                stillRunning: static fn(): HowTheBundleWent => new HowABundleReads()->running(),
                done: static fn(ABundle $bundle): HowTheBundleWent => new HowABundleReads()->done($bundle),
                refused: static fn(string $said): HowTheBundleWent => new HowABundleReads()->refused($said),
                ended: static fn(): HowTheBundleWent => new HowABundleReads()->ended(),
                met: fn(Obstacle $why): HowTheBundleWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheBundleWent => new HowABundleReads()->signedOut(),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): HowTheBundleWent
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowABundleReads()->met($why);
    }
}
