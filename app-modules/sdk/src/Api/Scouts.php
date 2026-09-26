<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundAlreadyHere;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what is already on its machine.
 *
 * Written the way {@see Doorkeepers} is, and for its reason: a survey that
 * could not be read is never a machine with nothing on it.
 */
final readonly class Scouts implements MovingIn
{
    public function __construct(private Clients $clients) {}

    public function surveyedOn(Stack $stack, Session $session): WhatWasFoundAlreadyHere
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::MIGRATION_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundAlreadyHere::found(WhatIsAlreadyHere::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundAlreadyHere::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|MigrationIsUnreadable) {
            return WhatWasFoundAlreadyHere::met(Obstacle::StackDidNotAnswer);
        }
    }
}
