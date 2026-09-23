<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereItComesFrom;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheOriginsTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereOneServiceComesFrom;

/**
 * What asking a stack where its services come from produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out, and nothing asked of anything on the way:
 * every word is the stack's, and no upstream is reached to check one.
 */
final readonly class HowTheOriginsRead
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking.
     */
    public function signedOut(): TheOriginsTurnedOutToBe
    {
        return new TheOriginsTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), services: []);
    }

    /** The stack answered, and this is where its services come from. */
    public function this(WhereTheServicesComeFrom $origins): TheOriginsTurnedOutToBe
    {
        $services = [];

        foreach ($origins as $origin) {
            $services[] = $this->one($origin);
        }

        return new TheOriginsTurnedOutToBe(went: HowTheReadingWent::itCameBack(), services: $services);
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheOriginsTurnedOutToBe
    {
        return new TheOriginsTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), services: []);
    }

    /** One service, as the row that draws it. */
    private function one(WhereItComesFrom $origin): WhereOneServiceComesFrom
    {
        return new WhereOneServiceComesFrom(
            name: $origin->name(),
            image: $origin->image(),
            pinned: $origin->pinned(),
            upstream: $origin->upstream(),
            licence: $origin->licence(),
        );
    }
}
