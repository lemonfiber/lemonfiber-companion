<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AServiceBeside;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Operator\Internal\ViewModels\AServiceBesideAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheFrontDoorTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheyBeginAsShown;

/**
 * What asking a stack for its front door produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Every address is handed on as the stack sent
 * it; nothing here builds one.
 */
final readonly class HowTheFrontDoorReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheFrontDoorTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is its door. */
    public function this(TheFrontDoor $door): TheFrontDoorTurnedOutToBe
    {
        $beside = [];

        foreach ($door->beside() as $service) {
            $beside[] = $this->beside($service);
        }

        return new TheFrontDoorTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            standingSaid: $door->standing()->saidOnTheScreen(),
            meaning: $door->meaning(),
            chosenSaid: $door->chosen()->how()->saidOnTheScreen(),
            named: $door->chosen()->named(),
            refusal: $door->chosen()->because(),
            begins: $door->begins()->either(
                at: static fn(string $service, WhatItFaces $facing, AnAddressToHand $address): WhereTheyBeginAsShown => new WhereTheyBeginAsShown(
                    service: $service,
                    facingSaid: $facing->saidOnTheScreen(),
                    url: $address->url(),
                    caution: $address->caution(),
                ),
                nowhere: WhereTheyBeginAsShown::nowhere(...),
            ),
            beside: $beside,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheFrontDoorTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** One service beside the door, as the row that draws it. */
    private function beside(AServiceBeside $service): AServiceBesideAsShown
    {
        return new AServiceBesideAsShown(
            service: $service->service(),
            facingSaid: $service->facing()->saidOnTheScreen(),
            because: $service->because(),
            url: $service->address()->url(),
            caution: $service->address()->caution(),
        );
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheFrontDoorTurnedOutToBe
    {
        return new TheFrontDoorTurnedOutToBe(
            went: $went,
            standingSaid: '',
            meaning: '',
            chosenSaid: '',
            named: '',
            refusal: '',
            begins: WhereTheyBeginAsShown::nowhere(),
            beside: [],
        );
    }
}
