<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TakingCopies;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\Presenters\HowACopyReads;
use Modules\Operator\Internal\Presenters\HowAScopeReads;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Taking a copy of this machine's stack: of all of it, or of one service.
 *
 * **The scope is named before it starts and again on the result.** Choosing
 * one asks first, naming what the copy would cover; only the yes sends it.
 * The report says what it covered, which older copies it removed and how its
 * size stood against the minute a copy is meant to take.
 *
 * **Taking a copy answers a handle,** and the report arrives only through it.
 * The handle is held, asked after on a stated cadence while the stack is
 * taking the copy, and the report drawn once it finishes. The stack says
 * nothing about how far a copy has got until it has finished, so while it
 * runs the screen says that and nothing more.
 *
 * The services a copy can be narrowed to are the ones the stack says it runs,
 * read once per frame.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class TakingACopyHere extends NativeComponent
{
    use AsksWhatTheStackIsRunning;

    /**
     * The copy being asked about, while the operator decides.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason, and held
     * so the copy sent is the one the question named.
     */
    public ?ACopyAsked $asking = null;

    /** The copy that was sent, so what it covers can be said while it runs. */
    public ?ACopyAsked $taking = null;

    /** The handle taking it answered. Not shown and never kept past this screen. */
    public ?string $took = null;

    /** What became of it, once this frame has asked. */
    public ?HowTheCopyWent $lastCopy = null;

    public function __construct(
        private readonly TakingCopies $copying,
        private readonly Supervising $supervising,
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
        return view('operator::taking-a-copy-here');
    }

    /** The services a copy can be narrowed to, asked once per frame. */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->answered ??= $this->askWhatIsRunning($this->stack(), $this->storage, $this->supervising);
    }

    /** Ask about a copy of the whole stack. */
    public function copyTheWholeStack(): void
    {
        $this->asking = ACopyAsked::ofTheWholeStack();
    }

    /**
     * Ask about a copy of one of the services the stack listed.
     *
     * Silent for a name the listing did not carry: a copy is only ever of a
     * service this screen showed, and a blank names none.
     */
    public function copyTheService(string $named): void
    {
        if (trim($named) === '') {
            return;
        }

        $asked = ServiceId::called($named);

        foreach ($this->answer()->services as $service) {
            if ($service->id->isTheSameAs($asked)) {
                $this->asking = ACopyAsked::ofOneService($service->id);

                return;
            }
        }
    }

    /** What the copy being asked about would cover, where one is. */
    public function askingAbout(): ?AScopeAsShown
    {
        return $this->asking instanceof ACopyAsked ? new HowAScopeReads()->of($this->asking->scope()) : null;
    }

    /** Take the copy that was asked about. */
    public function agree(): void
    {
        $asked = $this->asking;

        if (! $asked instanceof ACopyAsked) {
            return;
        }

        $this->asking = null;
        $this->takeIt($asked);
    }

    /** Leave it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /** What became of the copy taken here, or that none was. */
    public function lastCopy(): HowTheCopyWent
    {
        return $this->lastCopy ??= $this->followed();
    }

    /**
     * Ask after the copy again while the stack is taking it.
     *
     * It does nothing unless a copy is being taken, so a finished report is
     * not read over and over. The interval is {@see HowOften}'s constant,
     * which {@see cadence()} states on the screen.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->lastCopy()->isWorking) {
            $this->lastCopy = null;
        }
    }

    /** Ask the stack again: the services, and what became of the copy. */
    public function again(): void
    {
        $this->answered = null;
        $this->lastCopy = null;
    }

    /**
     * Send the copy agreed to, and hold what to follow it by.
     *
     * A refusal is kept as what became of it, so the screen says what stood
     * in the way rather than carrying on as though a copy were being taken.
     */
    private function takeIt(ACopyAsked $asked): void
    {
        $stack = $this->stack();
        $this->took = null;
        $this->taking = $asked;

        $this->lastCopy = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheCopyWent => $this->copying->take($stack, $session, $asked)->either(
                started: function (Job $job) use ($asked): HowTheCopyWent {
                    $this->took = $job->shown();

                    return new HowACopyReads()->running($asked->scope());
                },
                met: fn(Obstacle $why): HowTheCopyWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheCopyWent => new HowACopyReads()->signedOut(),
        );
    }

    /** Ask the stack what became of the copy taken, or say that none was. */
    private function followed(): HowTheCopyWent
    {
        $took = $this->took;
        $asked = $this->taking;

        if ($took === null || ! $asked instanceof ACopyAsked) {
            return new HowACopyReads()->notAsked();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheCopyWent => $this->copying->whatBecameOf($stack, $session, Job::named($took))->either(
                stillRunning: static fn(): HowTheCopyWent => new HowACopyReads()->running($asked->scope()),
                done: static fn(ACopyTaken $report): HowTheCopyWent => new HowACopyReads()->done($report),
                ended: static fn(): HowTheCopyWent => new HowACopyReads()->ended($asked->scope()),
                met: fn(Obstacle $why): HowTheCopyWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowTheCopyWent => new HowACopyReads()->signedOut(),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): HowTheCopyWent
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowACopyReads()->met($why);
    }
}
