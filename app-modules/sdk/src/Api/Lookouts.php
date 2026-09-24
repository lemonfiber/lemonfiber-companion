<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Outgoing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundLeaving;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what leaves it.
 *
 * Beside {@see Archivists} and written the same way: it asks {@see Clients}
 * for the connection rather than building one, which keeps the certificate pin
 * in a single file.
 *
 * **Four raises, two answers**, which is {@see Recorders}' collapse for its
 * reason. A refused session is told apart by {@see WhatARefusalMeant}.
 *
 * **A list that could not be read is never an empty one.** Both would draw a
 * machine that sends nothing, and only one of them is true.
 */
final readonly class Lookouts implements Outgoing
{
    public function __construct(private Clients $clients) {}

    public function leaving(Stack $stack, Session $session): WhatWasFoundLeaving
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::OUTBOUND_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundLeaving::leaving(WhatLeaves::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundLeaving::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|OutboundIsUnreadable) {
            return WhatWasFoundLeaving::met(Obstacle::StackDidNotAnswer);
        }
    }
}
