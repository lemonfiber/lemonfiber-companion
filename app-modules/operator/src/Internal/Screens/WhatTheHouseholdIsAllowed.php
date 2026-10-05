<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheAllowanceReads;
use Modules\Operator\Internal\ViewModels\TheAllowanceTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * What this machine says the household may ask it for, on the operator's side.
 *
 * The household's own reading, asked with the operator's session and drawn
 * with the operator's menu: the core decides what comes back for whoever is
 * signed in, and this screen shows it in the core's words.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatTheHouseholdIsAllowed extends NativeComponent
{
    use LooksAgainWhileOpen;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-the-household-is-allowed';

    /** What came back, once the frame has asked. */
    public ?TheAllowanceTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Ask the stack again. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** What the stack said, asked once per frame. */
    public function answer(): TheAllowanceTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    private function ask(): TheAllowanceTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAllowanceTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheAllowanceTurnedOutToBe => new HowTheAllowanceReads()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): TheAllowanceTurnedOutToBe
    {
        return $this->owing->toHandOver($stack, $session)->either(
            told: static fn(Sentences $said): TheAllowanceTurnedOutToBe => new HowTheAllowanceReads()->these($said),
            refused: function (Obstacle $why) use ($stack): TheAllowanceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAllowanceReads()->met($why);
            },
        );
    }
}
