<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\Generated\SpaceAction;
use Modules\Kernel\Api\ARoomGivenBackAgreed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\GivingBackRoom;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheOfferOfRoom;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Giving back room through the SDK: the `space` action, sent with the offer's name and the one category agreed to, and followed.
 *
 * A yes refused because the offer moved answers as moved.
 */
final readonly class RoomGivers implements GivingBackRoom
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheRoomGivenBack>
     */
    public function giveBack(Stack $stack, Session $session, ARoomGivenBackAgreed $agreed): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new SpaceAction(only: [$agreed->category()->value], offer: $agreed->agreement()));
    }

    /**
     * @return WhatBecameOfTheWork<TheRoomGivenBack>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheRoomGivenBack => TheOfferOfRoom::cameBackIn($envelope));
        } catch (SpaceIsUnreadable $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy, RefusalCode::AnotherOffer);
    }
}
