<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\Welcoming;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheFrontDoorReads;
use Modules\Operator\Internal\ViewModels\TheFrontDoorTurnedOutToBe;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The household's front door: where it stands, whether it was chosen or worked out, and what else they can reach.
 *
 * Every address on it is one the stack sent, with the stack's caution beside
 * it; none is put together here. Each service beside the door says what it
 * is to the household and why it is not the door.
 *
 * It names no door and asks once, when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhereTheHouseholdComesIn extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheFrontDoorTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Welcoming $welcoming,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}


    /** Ask the machine again, which an obstacle must not take away. */
    public function again(): void
    {
        $this->answered = null;
    }


    public function render(): View
    {
        return view('operator::where-the-household-comes-in');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheFrontDoorTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheFrontDoorTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheFrontDoorTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheFrontDoorTurnedOutToBe => new HowTheFrontDoorReads()->signedOut(),
        );
    }

    /** What the machine said of its door, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheFrontDoorTurnedOutToBe
    {
        return $this->welcoming->frontDoorOf($stack, $session)->either(
            found: static fn(TheFrontDoor $door): TheFrontDoorTurnedOutToBe => new HowTheFrontDoorReads()->this($door),
            met: function (Obstacle $why) use ($stack): TheFrontDoorTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheFrontDoorReads()->met($why);
            },
        );
    }
}
