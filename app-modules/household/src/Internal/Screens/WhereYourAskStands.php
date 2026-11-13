<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\Presenters\HowYourAskReads;
use Modules\Household\Internal\ViewModels\WhatYourAskTurnedOutToBe;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * One music or book ask of the member's, opened from Home.
 *
 * Its lettered poster, its name and kind, and where it stands drawn as the one
 * action, not usable: the app plays films and series only. Once it is here, a
 * line says where in the house to find it, naming no service. It reads what the
 * Requests tab reads and asks the core nothing of its own.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhereYourAskStands extends NativeComponent
{
    use AsksTheStackAgain;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::where-your-ask-stands';

    /** What came back, once the frame has asked. */
    public ?WhatYourAskTurnedOutToBe $read = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
    ) {}

    public function again(): void
    {
        $this->read = null;
    }

    /** The ask the route names, read once and held until asked again. */
    public function ask(): WhatYourAskTurnedOutToBe
    {
        return $this->read ??= $this->asked();
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    private function asked(): WhatYourAskTurnedOutToBe
    {
        $stack = $this->stack();
        $reference = $this->around->aboutOn($this);

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatYourAskTurnedOutToBe => $this->owing->theirRequests($stack, $session)->asked()->either(
                told: static fn(Requested $wanted): WhatYourAskTurnedOutToBe => new HowYourAskReads()->among($wanted, $reference),
                refused: function (Obstacle $why) use ($stack): WhatYourAskTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowYourAskReads()->met($why);
                },
            ),
            notHeld: static fn(): WhatYourAskTurnedOutToBe => new HowYourAskReads()->signedOut(),
        );
    }
}
