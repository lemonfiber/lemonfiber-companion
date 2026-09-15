<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Supervising;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowSomethingElseReads;
use Modules\Operator\Internal\ViewModels\WhatElseTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What is running on this machine that its own configuration never declared.
 *
 * `N2-R21` asks for three things and this screen is the first of them: the
 * containers must be *reachable*. A screen of its own rather than a section of
 * {@see WhatThisStackRuns}, because that screen is the stack — it lists what
 * the stack runs and offers start, stop and restart against each row, and a
 * container nobody declared appearing in that list is precisely what the
 * requirement forbids. Two screens is the version of *MUST NOT present one as
 * part of the stack* that survives somebody adding a row.
 *
 * **It offers nothing to do.** Not an omission and not a screen half-built: the
 * requirement forbids a verb against one of these, and the value the reader
 * hands over has no identifier of the kind a verb accepts, so there is nothing
 * to wire even if a later hand wanted to. What it offers instead is the way
 * back and the way to ask again, which `N1-R3` and `N1-R27` require of every
 * screen whatever it is about.
 *
 * **Empty is an answer, and it is the usual one.** A machine running only what
 * its stack declares is the expected shape. The screen says so in a sentence
 * rather than showing an empty list, because nothing on a screen is
 * indistinguishable from still loading and from a stack that could not be
 * reached — three different situations with three different things to do.
 */
#[Lazy]
final class WhatElseIsRunningHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `protected` for {@see WhatStoppedComingIn::$answered}'s reason —
     * `NativeComponent` assigns it from the parent class, and a private member
     * of a subclass becomes a dynamic property the screen then silently stops
     * holding.
     */
    protected ?WhatElseTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Supervising $supervising,
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

    /** How many are shown, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->running);
    }

    /**
     * Ask the machine again (`N1-R3`).
     *
     * Forgetting what came back rather than re-reading here, so the next
     * accessor asks and the frame that starts is the one somebody tapped for.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-else-is-running-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatElseTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Resume the session, ask the machine, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatElseTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatElseTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatElseTurnedOutToBe => new HowSomethingElseReads()->signedOut(),
        );
    }

    /**
     * What the machine said, or what the operator met instead.
     *
     * The undeclared list is read off the answer rather than out of the
     * `these` arm, because it is true whichever arm applies: a stack that could
     * not be reached reports nothing undeclared, and that is an empty list
     * rather than a question left open.
     */
    private function asked(Stack $stack, Session $session): WhatElseTurnedOutToBe
    {
        $answer = $this->supervising->running($stack, $session);

        return $answer->either(
            // The listing itself is not read here: this screen is about what the
            // stack did *not* declare, and the arm is only being asked which of
            // the two answers came back.
            these: static fn(): WhatElseTurnedOutToBe
                => new HowSomethingElseReads()->these($answer->whatElseIsRunning()),
            met: function (Obstacle $why) use ($stack): WhatElseTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowSomethingElseReads()->met($why);
            },
        );
    }
}
