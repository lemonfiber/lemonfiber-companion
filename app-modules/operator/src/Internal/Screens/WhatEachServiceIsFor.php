<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheCatalogueReads;
use Modules\Operator\Internal\ViewModels\TheCatalogueTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * What each service on this machine is for, and what became of any it dropped.
 *
 * A list of what the stack could run is not a list of software: each entry
 * leads with what the service does for the house and what the house goes
 * without while it is down, and names the service after. A service the stack
 * dropped names why, and what took its place where anything did, so an
 * operator looking for one they remember is answered rather than told it is
 * not there.
 *
 * **It asks once, when the frame is built, and holds what came back**, which
 * is {@see WhereThisComesFrom}'s shape.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatEachServiceIsFor extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::what-each-service-is-for';

    /**
     * What came back, once the frame has asked.
     *
     * `public` for {@see WhereThisComesFrom::$answered}'s reason.
     */
    public ?TheCatalogueTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Cataloguing $catalogue,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** What came back, asked once per frame. */
    public function answer(): TheCatalogueTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheCatalogueTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCatalogueTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheCatalogueTurnedOutToBe => new HowTheCatalogueReads()->signedOut(),
        );
    }

    /** What the machine said its services are for, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheCatalogueTurnedOutToBe
    {
        return $this->catalogue->describedOn($stack, $session)->either(
            catalogue: static fn(TheCatalogue $catalogue): TheCatalogueTurnedOutToBe
                => new HowTheCatalogueReads()->this($catalogue),
            refused: static fn(ARefusalInItsWords $why): TheCatalogueTurnedOutToBe
                => new HowTheCatalogueReads()->refused($why),
            met: $this->lettingGoIfRefused($stack, new HowTheCatalogueReads()->met(...)),
        );
    }
}
