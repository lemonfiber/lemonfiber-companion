<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChoosesWhatABundleHolds;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowABundleReads;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\ABundleAsShown;
use Modules\Operator\Internal\ViewModels\HowTheBundleWent;
use Modules\Operator\Internal\WhatHandingOverCameTo;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Asking for help: a support bundle, described, written, and handed over by the operator.
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
 * **Both answer a handle,** followed on a declared cadence while the stack
 * gathers. A bundle the stack refused is its answer, drawn as a refusal in its
 * own words and never as a fault to try again.
 *
 * **Handing it over is a third tap, and it is the operator's.** A written
 * bundle offers it: the file is fetched from the stack and put in front of the
 * device's own sharing, where the operator chooses where it goes. This screen
 * adds nothing to the bundle and sends it nowhere itself, and what it says
 * afterwards is only whether the sheet was reached.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class AskingForHelpHere extends NativeComponent implements AwaitsAnOutcome
{
    use ChoosesWhatABundleHolds;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

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

    /** The catalogue key for what handing the bundle over came to, or empty where it has not been. */
    public string $handing = '';

    /** The key for what stands after it, beside {@see $handing}. */
    public string $handingLeaves = '';

    public function __construct(
        private readonly AskingForHelp $helping,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        private readonly Sharing $sharing,
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
        return $this->answer()->isWorking;
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

    /**
     * Hand the written bundle over through the device's own sharing.
     *
     * Silent unless a written bundle is what the stack last answered with. The
     * file is fetched from the stack and handed to the sheet, and what is said
     * afterwards is whether the sheet was reached, or what stood in the way:
     * the contents already drawn stand either way, and nothing is retried.
     */
    public function handOver(): void
    {
        $bundle = $this->answer()->bundle;

        if (! $this->answer()->isWritten || ! $bundle instanceof ABundleAsShown) {
            return;
        }

        $stack = $this->stack();
        $written = AWrittenBundle::at($bundle->where);

        $came = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatHandingOverCameTo => $this->helping->fetch($stack, $session, $written)->either(
                fetched: fn(ABundleFile $file): WhatHandingOverCameTo => $this->handed($file),
                met: function (Obstacle $why) use ($stack): WhatHandingOverCameTo {
                    $this->letGoOfTheSession($why, $stack);

                    return WhatHandingOverCameTo::stoppedBy($why->said());
                },
            ),
            notHeld: function (): WhatHandingOverCameTo {
                $this->went = new HowABundleReads()->signedOut();

                return WhatHandingOverCameTo::nothing();
            },
        );

        $this->handing = $came->said;
        $this->handingLeaves = $came->leaves;
    }

    /** Go back to the choices, keeping them, and forget the bundle followed. */
    public function startOver(): void
    {
        $this->choosing = true;
        $this->asked = null;
        $this->handle = null;
        $this->went = null;
        $this->handing = '';
        $this->handingLeaves = '';
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
     * over and over. The interval is {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->went = null;
        }
    }

    /**
     * The fetched file, put in front of the device's own sharing, and what the sheet answered.
     *
     * One sentence per refusal, because the remedies differ: a file this phone
     * could not hold is answered by freeing room, and a sheet that would not
     * open by the other roads the phone has.
     */
    private function handed(ABundleFile $file): WhatHandingOverCameTo
    {
        return $this->sharing->handOver($file)->either(
            over: WhatHandingOverCameTo::offered(...),
            refused: static fn(WhyNothingWasShared $why): WhatHandingOverCameTo => WhatHandingOverCameTo::stoppedBy(match ($why) {
                WhyNothingWasShared::NothingToHandOver => 'stacks.help.not_held_here',
                WhyNothingWasShared::TheDeviceWouldNotOffer => 'stacks.help.not_offered',
            }),
        );
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
        $this->handing = '';
        $this->handingLeaves = '';

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
                refused: static fn(ARefusalInItsWords $why): HowTheBundleWent => new HowABundleReads()->refused($why),
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
