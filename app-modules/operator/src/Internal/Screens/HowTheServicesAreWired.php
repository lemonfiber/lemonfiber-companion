<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WiringTheServices;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\Presenters\HowTheWiringReads;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\TheWiringTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Wiring a stack's services to each other, and how each connection turned out.
 *
 * **A run is offered as the act it is.** It changes nothing already right and
 * keeps what the operator changed, so there is no question before it. What
 * the screen opens on is the services the stack runs, which are what a run
 * wires to each other, read once per frame the way {@see TakingACopyHere}
 * reads them; nothing is wired until the run is tapped.
 *
 * **Every connection is drawn in the state the stack gave it**, with the
 * stack's words, and a run that only said what it would do says so first.
 * Nothing here offers to put lemonfiber's value back over the operator's.
 *
 * **The run is work the stack names and this follows**, so the handle is held
 * and asked after at {@see HowOften::WhileWorkRuns} while it runs, the way
 * {@see AskingSomebodyIn} follows an invitation.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class HowTheServicesAreWired extends NativeComponent
{
    use AsksWhatTheStackIsRunning;

    /** The handle of the run being followed, while there is one. */
    public ?string $following = null;

    /** Where the run has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheWiringTurnedOutToBe $going = null;

    public function __construct(
        private readonly WiringTheServices $wiring,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
    ) {}

    /** The stack this screen is about, read from the route on every frame. */
    public function stack(): Stack
    {
        return $this->around->stackNamed($this->param('stack'));
    }

    /** The services the stack runs, which a run wires to each other; asked once per frame. */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->answered ??= $this->askWhatIsRunning($this->stack(), $this->storage, $this->supervising);
    }

    /** Start a wiring run. */
    public function wire(): void
    {
        $this->following = null;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->wire($stack, $session),
        );
    }

    /**
     * Ask again, which an obstacle must not take away.
     *
     * Asks for the services again, and after the run being followed where
     * there is one. An answer the stack gave about a run — its report, its
     * refusal, or its having no outcome — stays on the screen, because there
     * is nothing further to ask about it. An obstacle met starting a run is
     * let go of, and the request that never reached the stack is not sent
     * again on its own.
     */
    public function again(): void
    {
        $this->answered = null;
        $going = $this->going;

        if ($this->following !== null || ! $going instanceof TheWiringTurnedOutToBe || ! $going->went->cameBack()) {
            $this->going = null;
        }
    }

    /**
     * Ask after the run again while the stack is carrying it out.
     *
     * Nothing happens unless it is running, so a finished answer is not asked
     * for again. The interval is {@see HowOften}'s.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::how-the-services-are-wired');
    }

    /**
     * Where the run has got to, asked once per frame.
     *
     * Asks the stack after the run being followed where there is one, and
     * otherwise says nothing has been asked.
     */
    public function howItIsGoing(): TheWiringTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheWiringReads()->notAsked()
            : $this->put(
                fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->whatBecameOf($stack, $session, Job::named($following)),
            );
    }

    /**
     * One request put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWiring $asking
     */
    private function put(Closure $asking): TheWiringTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheWiringTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheWiringTurnedOutToBe => new HowTheWiringReads()->signedOut(),
        );
    }

    /** What the stack said, as the screen draws it, holding what to follow. */
    private function shown(WhatBecameOfTheWiring $became, Stack $stack): TheWiringTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheWiringTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheWiringReads()->running();
            },
            answered: function (TheWiring $wiring): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->answered($wiring);
            },
            ended: function (): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->ended();
            },
            refused: function (string $because): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->refused($because);
            },
            met: function (Obstacle $why) use ($stack): TheWiringTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheWiringReads()->met($why);
            },
        );
    }
}
