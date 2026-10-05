<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheRecordReads;
use Modules\Operator\Internal\ViewModels\TheRecordTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

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
 * **Nothing here undoes anything.** Each moment leads to
 * {@see PuttingThatRunBack}, which says what goes with the run before anything
 * is agreed to, so there is one place that decision is made rather than a
 * second one on every row.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * changed is the household's business, and a diagnostic report is assembled
 * from what the operator chooses to send rather than from what a screen held.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatWasChangedHere extends NativeComponent
{
    use LooksAgainWhileOpen;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-was-changed-here';

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
        protected readonly TheWayAround $around,
        private readonly Clock $clock,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

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
