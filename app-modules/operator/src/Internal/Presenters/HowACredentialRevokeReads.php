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
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCredentialRevokeTurnedOutToBe;

/**
 * Where ending a grant has got to, as the template draws it.
 */
final readonly class HowACredentialRevokeReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** The stack is still at it. */
    public function running(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheCredentialRevokeTurnedOutToBe
    {
        return new TheCredentialRevokeTurnedOutToBe(went: HowTheReadingWent::itCameBack(), isWorking: false, hasEnded: false, refused: new HowARefusalReads()->inItsWords($why), revoke: null);
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheCredentialRevokeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** What the stack read of ending the grant, or what ending it came to, dated against the frame's moment. */
    public function answered(WhatARevokeCameTo $came, Instant $now): TheCredentialRevokeTurnedOutToBe
    {
        $revoke = $came->revoke();

        return new TheCredentialRevokeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
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

    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false): TheCredentialRevokeTurnedOutToBe
    {
        return new TheCredentialRevokeTurnedOutToBe(went: $went, isWorking: $isWorking, hasEnded: $hasEnded, refused: null, revoke: null);
    }
}
