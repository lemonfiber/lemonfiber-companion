<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KnowingWhereAlertsGo;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhereAlertsGoReads;
use Modules\Operator\Internal\ViewModels\WhereAlertsGoTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/** Where this machine's alerts go: how delivery stands, every back-end in the core's order, and the ones the core could not read. */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhereAlertsGoHere extends NativeComponent
{
    use AsksTheStackAgain;
    use LooksAgainWhileItMoves;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::where-alerts-go-here';

    public ?WhereAlertsGoTurnedOutToBe $answered = null;

    public function __construct(
        private readonly KnowingWhereAlertsGo $alerts,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function answer(): WhereAlertsGoTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    private function ask(): WhereAlertsGoTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhereAlertsGoTurnedOutToBe => $this->asked($stack, $session),
            notHeld: HowWhereAlertsGoReads::signedOut(...),
        );
    }

    private function asked(Stack $stack, Session $session): WhereAlertsGoTurnedOutToBe
    {
        return $this->alerts->whereAlertsGoOn($stack, $session)->either(
            read: fn(WhereAlertsGo $read): WhereAlertsGoTurnedOutToBe => new HowWhereAlertsGoReads($this->goes(), $this->clock->now())->all($read),
            met: $this->lettingGoIfRefused($stack, HowWhereAlertsGoReads::met(...)),
        );
    }
}
