<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingVersions;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheVersionsRead;
use Modules\Operator\Internal\ViewModels\TheVersionsTurnedOutToBe;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Which versions this machine runs — lemonfiber, the stack, the container engine — and what the running release changed.
 *
 * The detail of what {@see WhatIsRunningHere} says is running, and reached
 * from there. Each version is explained where it is shown. The running
 * release's notes are drawn only where the stack says they describe this copy,
 * with whether the household would notice it and whether it was taken back;
 * notes not written yet and notes out of step are each said as what they are.
 *
 * **It reads and changes nothing.** It asks once, when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhichVersionsRunHere extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /** What came back, once the frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheVersionsTurnedOutToBe $answered = null;

    public function __construct(
        private readonly ReadingVersions $reading,
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
        return view('operator::which-versions-run-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheVersionsTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheVersionsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheVersionsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheVersionsTurnedOutToBe => new HowTheVersionsRead()->signedOut(),
        );
    }

    /** What the machine said it runs, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheVersionsTurnedOutToBe
    {
        return $this->reading->versionsOn($stack, $session)->either(
            found: static fn(WhatRunsHere $runs): TheVersionsTurnedOutToBe => new HowTheVersionsRead()->these($runs),
            met: function (Obstacle $why) use ($stack): TheVersionsTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheVersionsRead()->met($why);
            },
        );
    }
}
