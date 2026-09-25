<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Advising;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundToWatchOn;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack which app to watch on.
 *
 * Written the way {@see Lookouts} is, and for its reason: advice that could
 * not be read is never advice with nothing in it.
 */
final readonly class Advisers implements Advising
{
    public function __construct(private Clients $clients) {}

    public function advisedBy(Stack $stack, Session $session): WhatWasFoundToWatchOn
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::CLIENTS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundToWatchOn::found(WhatToWatchWith::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundToWatchOn::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|ClientsIsUnreadable) {
            return WhatWasFoundToWatchOn::met(Obstacle::StackDidNotAnswer);
        }
    }
}
