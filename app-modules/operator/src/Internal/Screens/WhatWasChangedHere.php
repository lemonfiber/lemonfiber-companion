<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheRecord;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheRecordReads;
use Modules\Operator\Internal\ViewModels\TheRecordTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine has changed about itself, and how far each change goes back.
 *
 * The question an operator asks after something moved that they did not move:
 * what was done, by which operation, and whether it can be put back. It is the
 * other half of the hours nobody was looking — {@see WhatKeepsRunningHere}
 * says what was kept running, and this says what was changed.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhatStoppedComingIn}'s shape: one read per frame, and a home network
 * with a machine that may be asleep is the wrong thing to talk to four times a
 * second. The clock is read once per answer too, so every age on the screen is
 * measured against the same *now*.
 *
 * **Nothing here undoes anything.** Putting a change back is an action with an
 * agreement of its own, and a record offering it from a row would be a second
 * place that decision is made — the first time somebody used the other one,
 * the two would disagree about what had been undone.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * changed is the household's business, and a diagnostic report is assembled
 * from what the operator chooses to send rather than from what a screen held.
 */
#[Lazy]
#[Concealed]
final class WhatWasChangedHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. The same reason {@see WhatStoppedComingIn::$answered} gives.
     */
    public ?TheRecordTurnedOutToBe $answered = null;

    public function __construct(
        private readonly History $history,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
        private readonly Clock $clock,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatStoppedComingIn::stack()}'s reason: there is one answer to
     * *which machine*, and it is the one the URI names.
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
     * The action an obstacle must not take away, and one an operator who has
     * just changed something at the machine wants on a screen that answered.
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
        return view('operator::what-was-changed-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheRecordTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheRecordTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRecordTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheRecordTurnedOutToBe => new HowTheRecordReads()->signedOut(),
        );
    }

    /** What the machine's record said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheRecordTurnedOutToBe
    {
        return $this->history->recordedOn($stack, $session)->either(
            record: fn(TheRecord $record): TheRecordTurnedOutToBe
                => new HowTheRecordReads()->this($record, $this->clock->now()),
            met: function (Obstacle $why) use ($stack): TheRecordTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRecordReads()->met($why);
            },
        );
    }
}
