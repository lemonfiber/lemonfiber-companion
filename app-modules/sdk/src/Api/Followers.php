<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhatWasFoundOfTheTrace;
use Modules\Sdk\Api\Fields\TraceField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack where one item got to.
 *
 * Written the way {@see Inspectors} is: a read, naming the term to follow.
 */
final readonly class Followers implements Tracing
{
    public function __construct(private Clients $clients) {}

    public function tracedOn(Stack $stack, Session $session, WhatToFollow $following): WhatWasFoundOfTheTrace
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::TRACE_ENDPOINT, [TraceField::Term->value => $following->term()]);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheTrace::found(Traces::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfTheTrace::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|TraceIsUnreadable) {
            return WhatWasFoundOfTheTrace::met(Obstacle::StackDidNotAnswer);
        }
    }
}
