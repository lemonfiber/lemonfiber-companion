<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfTheCredentials;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack which credentials it holds.
 *
 * Written the way {@see Lookouts} is, and for its reason: a list that could
 * not be read is never an empty one. It asks the credentials endpoint and
 * nothing else — no action that reveals or replaces a credential is offered
 * on this surface, and none is asked for here.
 */
final readonly class Keyholders implements Safekeeping
{
    public function __construct(private Clients $clients) {}

    public function heldOn(Stack $stack, Session $session): WhatWasFoundOfTheCredentials
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::CREDENTIALS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheCredentials::found(CredentialsKept::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfTheCredentials::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|CredentialsIsUnreadable) {
            return WhatWasFoundOfTheCredentials::met(Obstacle::StackDidNotAnswer);
        }
    }
}
