<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\WhatOneRepairSays;
use Modules\Operator\Internal\WhatTheStackWouldPutRight;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this stack would put right, stated before anybody is asked to agree.
 *
 * `N2-R4` is the requirement and the order in it is the requirement: what a
 * repair does, what else it affects and whether it can be undone are said
 * *before* confirmation is asked for. So this screen exists on its own rather
 * than as a dialog behind a button — a sentence an operator has to tap to
 * reveal is one they will agree without reading.
 *
 * **Asking costs a round trip and answers a handle.** `N2-R7` has every action
 * on this surface arrive as a job, including the unconfirmed form that changes
 * nothing. The frame therefore asks and then reads, once each, and what comes
 * back may well be *still working on it* — which is a state of this screen
 * rather than something to hide behind a spinner that lies.
 *
 * **Asking again is a button, and which question it asks depends.** Where a job
 * is still running, it reads the same handle — the work is the stack's and
 * repeating the read changes nothing. Where the job ended, it starts a new one,
 * because there is nothing left to read. `N1-R17` keeps this from happening on
 * a timer: an operator on a home network with a machine that may be asleep
 * decides when to ask, and `N4-R4`'s argument about not re-asking for something
 * declined is the same argument one requirement over.
 *
 * **No yes here yet.** `N2-R5` has the agreeing be a separate act against a
 * named listing, and `Confirmed` is built and unreached. The listing's name is
 * carried on the fold for it, so that screen will not have to ask the stack
 * again for what this one is already showing.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a machine
 * would put right says a good deal about what is on it.
 */
#[Lazy]
#[Concealed]
final class WhatWouldBePutRight extends NativeComponent
{
    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatTheStackWouldPutRight $answered = null;

    /**
     * The handle, while there is one.
     *
     * Held so that asking again can read the same job rather than starting a
     * second one — two handles for one question is two lots of work on
     * somebody's machine. Not shown and never persisted: `N1-R41` refuses an
     * action presented as pending, and a job name on the glass is exactly that.
     */
    protected ?string $handle = null;

    public function __construct(
        private readonly Mending $mending,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Whether this device still holds a session for it (`N1-R44`). */
    public function isSignedIn(): bool
    {
        return $this->answer()->isSignedIn;
    }

    /** Whether the stack is still working out what it would do. */
    public function isWorkingItOut(): bool
    {
        return $this->answer()->isWorking;
    }

    /** Whether the stack has no outcome for that asking any more. */
    public function hasEnded(): bool
    {
        return $this->answer()->hasEnded;
    }

    /**
     * Each repair, as a row a template can read.
     *
     * @return list<WhatOneRepairSays>
     */
    public function repairs(): array
    {
        return $this->answer()->repairs;
    }

    /** How many are offered, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->repairs);
    }

    /** What the operator met instead, as a key, or the empty string. */
    public function met(): string
    {
        return $this->answer()->met;
    }

    /** What to do about it, beside {@see met()}. */
    public function remedy(): string
    {
        return $this->answer()->remedy;
    }

    /**
     * Ask again, because the operator said so.
     *
     * Forgetting what was held rather than comparing, which is
     * {@see HowThisStackIs::again()}'s shape: the next read rebuilds it, so
     * there is one path to an answer.
     *
     * **The handle is kept only while the work is still going.** That is the
     * whole of what makes this button mean something. A job that has finished
     * answers the same listing however often it is read, so keeping its handle
     * would make *ask again* re-render what is already on the screen — and the
     * operator tapping it has just changed something on their machine and wants
     * to know whether it took. A job that ended has nothing to read at all. In
     * both cases the next frame starts fresh; only a run still in progress is
     * worth returning to, because reading it again is the only way to learn it
     * has finished.
     */
    public function again(): void
    {
        if ($this->answered?->isWorking !== true) {
            $this->handle = null;
        }

        $this->answered = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs}
     * is the only place that knows a stack's routes — six classes were each
     * spelling `/stacks/%s/sign-in` for themselves, so a rename had to be found
     * in all six and the one that was missed would be a button leading nowhere.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-would-be-put-right');
    }

    /** What came back, asked once per frame. */
    private function answer(): WhatTheStackWouldPutRight
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Resume the session, then ask the stack.
     *
     * Split from {@see answer()} because the two are different questions —
     * when to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheStackWouldPutRight
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackWouldPutRight => $this->read($stack, $session),
            notHeld: static fn(): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::signedOut(),
        );
    }

    /**
     * Read the handle, starting the work first where there is none.
     *
     * The two questions in the order `N2-R7` puts them, and the early return is
     * what makes *ask again* a second read rather than a second piece of work
     * on somebody's machine: a handle already in hand is read, and only the
     * absence of one starts anything.
     *
     * The name is kept inside the `started` arm rather than after the call,
     * because that is the only place it exists — the other arm has no handle to
     * keep, which is exactly what an `either()` is for.
     */
    private function read(Stack $stack, Session $session): WhatTheStackWouldPutRight
    {
        $held = $this->handle;

        if (is_string($held)) {
            return $this->became($stack, $session, Job::named($held));
        }

        return $this->mending->wouldPutRight($stack, $session)->either(
            started: function (Job $job) use ($stack, $session): WhatTheStackWouldPutRight {
                $this->handle = $job->shown();

                return $this->became($stack, $session, $job);
            },
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::met($why),
        );
    }

    /** What became of that handle, folded for the template. */
    private function became(Stack $stack, Session $session, Job $job): WhatTheStackWouldPutRight
    {
        return $this->mending->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): WhatTheStackWouldPutRight
                => WhatTheStackWouldPutRight::stillWorkingItOut(),
            offering: static fn(Offer $offer): WhatTheStackWouldPutRight
                => WhatTheStackWouldPutRight::offering($offer),
            ended: static fn(): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::ended(),
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight
                => WhatTheStackWouldPutRight::met($why),
        );
    }
}
