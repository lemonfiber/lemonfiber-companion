<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasRecorded;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack for its record.
 *
 * Beside {@see Keepers} and written the same way: it asks {@see Clients} for
 * the connection rather than building one, which keeps the certificate pin in
 * a single file.
 *
 * **Four raises, two answers**, which is {@see Stalls}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone — they cannot see what was changed, and the machine
 * is where to look. A refused session is told apart by
 * {@see WhatARefusalMeant}, because it is a different sentence.
 *
 * **A record that could not be read is never an empty one.** Both would draw
 * the same blank list, and only one of them means nothing happened — which is
 * the reason a reading refused is an obstacle here rather than a shorter
 * answer.
 */
final readonly class Recorders implements History
{
    public function __construct(private Clients $clients) {}

    public function recordedOn(Stack $stack, Session $session): WhatWasRecorded
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::HISTORY_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Stalls::stoppedOn()} makes: a payload the client fetched
            // and this side could not read is, to an operator, one that never
            // arrived — and read outside, it would be an uncaught raise.
            return WhatWasRecorded::record(Records::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasRecorded::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HistoryIsUnreadable) {
            return WhatWasRecorded::met(Obstacle::StackDidNotAnswer);
        }
    }
}
