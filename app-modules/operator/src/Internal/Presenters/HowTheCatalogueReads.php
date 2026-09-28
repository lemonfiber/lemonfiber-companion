<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
use Modules\Operator\Internal\ViewModels\AServiceAsCatalogued;
use Modules\Operator\Internal\ViewModels\AServiceDroppedAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCatalogueTurnedOutToBe;
use Modules\Operator\Internal\WhatTheCatalogueNames;

/**
 * What asking a stack what its services are for produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out. Every word is the stack's.
 */
final readonly class HowTheCatalogueReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking.
     */
    public function signedOut(): TheCatalogueTurnedOutToBe
    {
        return new TheCatalogueTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), services: [], dropped: []);
    }

    /** The stack answered, and this is its catalogue. */
    public function this(TheCatalogue $catalogue): TheCatalogueTurnedOutToBe
    {
        $services = [];
        $dropped = [];

        foreach ($catalogue->services() as $service) {
            $services[] = $this->service($service);
        }

        foreach ($catalogue->dropped() as $went) {
            $dropped[] = $this->dropped($went);
        }

        return new TheCatalogueTurnedOutToBe(went: HowTheReadingWent::itCameBack(), services: $services, dropped: $dropped);
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheCatalogueTurnedOutToBe
    {
        return new TheCatalogueTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), services: [], dropped: []);
    }

    /** One service, as the row that draws it. */
    private function service(WhatAServiceIsFor $service): AServiceAsCatalogued
    {
        return new AServiceAsCatalogued(
            name: $service->name(),
            describes: $service->describes(),
            withoutIt: $service->withoutIt(),
            mattersSaid: $service->matters()->saidOnTheScreen(),
        );
    }

    /**
     * One dropped service, as the row that draws it.
     *
     * The empty replacement is the *nothing* arm rather than a default: the
     * value one layer down refuses a blank one.
     */
    private function dropped(AServiceDropped $went): AServiceDroppedAsShown
    {
        return new AServiceDroppedAsShown(
            id: $went->service()->named(),
            removedIn: $went->removedIn(),
            reason: $went->reason(),
            replacedBy: $went->replacement(
                by: static fn(string $what): WhatTheCatalogueNames => new WhatTheCatalogueNames($what),
                nothing: static fn(): WhatTheCatalogueNames => new WhatTheCatalogueNames(''),
            )->said,
        );
    }
}
