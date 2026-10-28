<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Operator\Internal\Listed;
use Modules\Operator\Internal\ViewModels\ACredentialRevokeAsShown;
use Modules\Operator\Internal\ViewModels\TheCredentialRevokeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where ending a grant has got to, as the template draws it.
 */
final readonly class HowACredentialRevokeReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    /** The stack is still at it. */
    public function running(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheCredentialRevokeTurnedOutToBe
    {
        return new TheCredentialRevokeTurnedOutToBe(stands: WhereTheWorkStands::answered(), refused: new HowARefusalReads()->inItsWords($why), revoke: null);
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** What the stack read of ending the grant, or what ending it came to, dated against the frame's moment. */
    public function answered(WhatARevokeCameTo $came, Instant $now): TheCredentialRevokeTurnedOutToBe
    {
        $revoke = $came->revoke();

        return new TheCredentialRevokeTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: null,
            revoke: new ACredentialRevokeAsShown(
                wasEnded: $revoke->wasEnded(),
                grant: new HowAGrantReads()->one($revoke->grant(), 0, $now),
                restarts: $this->listed($revoke->restarts()),
                unrestarted: $revoke->unrestarted(),
            ),
        );
    }

    /** The services, named and listed in their order. */
    private function listed(Services $services): string
    {
        $named = [];

        foreach ($services as $service) {
            $named[] = $service->named();
        }

        return Listed::these(...$named);
    }

    private function without(WhereTheWorkStands $stands): TheCredentialRevokeTurnedOutToBe
    {
        return new TheCredentialRevokeTurnedOutToBe(stands: $stands, refused: null, revoke: null);
    }
}
