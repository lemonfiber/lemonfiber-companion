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
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatIsToldReads;
use Modules\Operator\Internal\ViewModels\WhatIsToldTurnedOutToBe;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine will tell its operator about.
 *
 * The preset in force and what it means in the operator's terms, and every
 * kind of event set apart from it — heard whatever the preset says, or kept
 * quiet whatever it says. The question somebody asks before a night away:
 * *will this wake me, and for what?*
 *
 * **It reads and raises nothing.** What the operator hears about is the core's
 * decision, configured where the core is; this screen shows the setting and
 * sends no notification of its own.
 *
 * It asks once, when the frame is built, for {@see WhereThisComesFrom}'s
 * reason. `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatYouAreToldAbout extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhereThisComesFrom::$answered} gives.
     */
    public ?WhatIsToldTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Telling $telling,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
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
        return view('operator::what-you-are-told-about');
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatIsToldTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): WhatIsToldTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatIsToldTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatIsToldTurnedOutToBe => new HowWhatIsToldReads()->signedOut(),
        );
    }

    /** What the machine said its operator is told about, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatIsToldTurnedOutToBe
    {
        return $this->telling->toldAbout($stack, $session)->either(
            told: static fn(WhatTheOperatorIsTold $told): WhatIsToldTurnedOutToBe
                => new HowWhatIsToldReads()->this($told),
            met: function (Obstacle $why) use ($stack): WhatIsToldTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatIsToldReads()->met($why);
            },
        );
    }
}
