<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Welcoming;
use Modules\Kernel\Api\WhatWasFoundOfTheFrontDoor;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack where the household comes in.
 *
 * Written the way {@see Lookouts} is, and for its reason: a door that could
 * not be read is never a stack with no door.
 */
final readonly class Doorkeepers implements Welcoming
{
    public function __construct(private Clients $clients) {}

    public function frontDoorOf(Stack $stack, Session $session): WhatWasFoundOfTheFrontDoor
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::FRONT_DOOR_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheFrontDoor::found(WhereTheDoorIs::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheFrontDoor::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|FrontDoorIsUnreadable $why) {
            return WhatWasFoundOfTheFrontDoor::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
