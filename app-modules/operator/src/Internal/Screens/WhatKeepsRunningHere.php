<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_any;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatTheHandoverDid;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAHandoverReads;
use Modules\Operator\Internal\Presenters\HowHostingReads;
use Modules\Operator\Internal\ViewModels\WhatKeepsRunningTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatOneUnattendedCommandSays;
use Modules\Operator\Internal\ViewModels\WhatTheHandoverShows;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine keeps running when nobody is signed in.
 *
 * The question about the hours nobody was looking. Everything else this app
 * shows about a stack is true while somebody has it open; this is the one an
 * operator asks the morning after a reboot, and the one they cannot answer from
 * a phone any other way.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhatStoppedComingIn}'s shape and what is required: one read per frame,
 * and a home network with a machine that may be asleep is the wrong thing to
 * talk to four times a second.
 *
 * **A machine that configures nothing is not a machine with nothing running.**
 * The two draw the same empty list, and only one of them invites an operator to
 * go looking for a switch. So the sentence saying what to do instead is a field
 * the template branches on rather than a row it might or might not have, and
 * the reading refuses a machine that claims the first and carries no sentence.
 *
 * **Handing a command over is offered here, as an act of its own.** Each row
 * offers keeping it running and taking it back, and neither is sent until the
 * operator has said yes to a question naming the command — {@see agree()} is
 * the only thing that sends, and it sends what was asked about. Nothing else on
 * this screen reaches the machine's service manager.
 *
 * **What came back is reported as the stack said it.** Where the command now
 * stands, whether it was started, where its words are written, every file
 * written or taken back, and whether it was a rehearsal — never *installed*,
 * which says nothing about whether anything is running it.
 *
 * **A machine with no manager offers neither.** The stack has already said it
 * cannot perform the act there, and the sentence saying what to do instead is
 * drawn in place of the controls.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is assembled from
 * what the operator chooses to send rather than from what a screen happened to
 * hold.
 */
#[Lazy]
#[Concealed]
final class WhatKeepsRunningHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one. The same
     * reason {@see WhatStoppedComingIn::$answered} gives.
     */
    public ?WhatKeepsRunningTurnedOutToBe $answered = null;

    /** What the operator has been asked about, where handing a command over is waiting on a yes. Public for {@see $answered}'s reason. */
    public ?HostingAgreed $asking = null;

    /** What came of the last handing over, until another is asked about. Public for {@see $answered}'s reason. */
    public ?WhatTheHandoverShows $handedOver = null;

    public function __construct(
        private readonly Hosting $hosting,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the argument
     * {@see WhatStoppedComingIn::stack()} makes, and the same refusal for a
     * route naming a stack this device has forgotten.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Ask the machine again.
     *
     * The action an obstacle must not take away. A control is not hidden
     * because the stack is unreachable — the app offers it and reports the
     * failure — and an obstacle screen with nothing on it leaves no way back
     * but leaving and returning.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Ask whether to hand the command of that name to this machine.
     *
     * Held rather than sent: {@see agree()} is the only thing that sends.
     */
    public function wouldInstall(string $named): void
    {
        $this->wouldYouLike(HandingOver::Install, $named);
    }

    /**
     * Ask whether to take the command of that name back off this machine.
     *
     * Held rather than sent, for {@see wouldInstall()}'s reason.
     */
    public function wouldRemove(string $named): void
    {
        $this->wouldYouLike(HandingOver::Remove, $named);
    }

    /**
     * Carry out what the operator has just agreed to.
     *
     * It sends what was held and nothing a template passed in, so the command
     * that was asked about and the command that is handed over are the same
     * value.
     */
    public function agree(): void
    {
        $agreed = $this->asking;

        if (! $agreed instanceof HostingAgreed) {
            return;
        }

        $this->asking = null;

        $stack = $this->stack();

        $this->handedOver = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheHandoverShows => $this->handOver($stack, $session, $agreed),
            notHeld: static fn(): WhatTheHandoverShows => new HowAHandoverReads()->signedOut($agreed->named()),
        );

        // What was read is about the machine before the act, so the next
        // accessor asks again rather than drawing a listing the act changed.
        $this->answered = null;
    }

    /** Put the question away without doing anything about it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs} is
     * the only place that knows a stack's routes.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-keeps-running-here');
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatStoppedComingIn::answer()}'s shape and its argument: a method
     * per field is a method this class spends on saying nothing.
     */
    public function answer(): WhatKeepsRunningTurnedOutToBe
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
    private function ask(): WhatKeepsRunningTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatKeepsRunningTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatKeepsRunningTurnedOutToBe => new HowHostingReads()->signedOut(),
        );
    }

    /**
     * Hold the question, where the listing offers the act and names the command.
     *
     * Built from what was read rather than from what the template passed: a
     * name the listing does not carry is not asked about, and neither is any
     * name on a machine where the stack said it cannot host anything.
     */
    private function wouldYouLike(HandingOver $doing, string $named): void
    {
        $answer = $this->answer();

        if (! $answer->handsOver || ! $this->lists($answer, $named)) {
            return;
        }

        $this->handedOver = null;
        $this->asking = HostingAgreed::to($doing, $named);
    }

    /** Whether the listing carries a command of that name. */
    private function lists(WhatKeepsRunningTurnedOutToBe $answer, string $named): bool
    {
        return array_any($answer->commands, fn(WhatOneUnattendedCommandSays $command): bool => $command->name === $named);
    }

    /** Hand it to the port, and fold whichever arm came back. */
    private function handOver(Stack $stack, Session $session, HostingAgreed $agreed): WhatTheHandoverShows
    {
        return $this->hosting->handOver($stack, $session, $agreed)->either(
            did: static fn(WhatTheHandoverDid $did): WhatTheHandoverShows => new HowAHandoverReads()->did($did),
            refused: static fn(string $said): WhatTheHandoverShows => new HowAHandoverReads()->refused($agreed->named(), $said),
            met: function (Obstacle $why) use ($stack, $agreed): WhatTheHandoverShows {
                $this->letGoOfTheSession($why, $stack);

                return new HowAHandoverReads()->met($agreed->named(), $why);
            },
        );
    }

    /** What the machine said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatKeepsRunningTurnedOutToBe
    {
        return $this->hosting->keptRunningOn($stack, $session)->either(
            keeps: static fn(WhatRunsUnattended $running): WhatKeepsRunningTurnedOutToBe
                => new HowHostingReads()->this($running),
            met: function (Obstacle $why) use ($stack): WhatKeepsRunningTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowHostingReads()->met($why);
            },
        );
    }
}
