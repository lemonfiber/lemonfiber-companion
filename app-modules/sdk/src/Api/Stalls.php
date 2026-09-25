<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\ALimitSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stalling;
use Modules\Kernel\Api\WhatIsStuck;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what has stopped coming in.
 *
 * Every call to a stack goes through the SDK, so this sits beside
 * {@see Requests} and is written the same way: it asks {@see PinnedClients} for
 * the connection rather than building one, which is what keeps the certificate
 * pin in a single file. This class never names a client constructor, so it
 * cannot make a decision about whether a certificate is checked.
 *
 * **The endpoint takes nothing**, which is the whole of why this is the first
 * of the four to be built: there is no filter to get wrong and no
 * narrowing for a screen to be tempted into asking for row by row.
 *
 * **Four raises, two answers**, which is {@see Requests}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone — they cannot see what has stalled, and the machine
 * is where to look. The exception is a refused session, which is a different
 * sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Admissions}'
 * reason: the SDK raises one where a stored stack cannot be pinned, which means
 * this app is holding a stack it should never have written down. Catching it
 * would turn a fault in retained state into an ordinary screen about an
 * unreachable machine.
 */
final readonly class Stalls implements Stalling
{
    public function __construct(private Clients $clients) {}

    public function stoppedOn(Stack $stack, Session $session): WhatIsStuck
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::STUCK_ENDPOINT);

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Requests::askedOf()} makes. A payload the client fetched
            // and this side could not read is the same thing to an operator as
            // one that never arrived, and reading it outside would put an
            // uncaught raise on a screen instead.
            return WhatIsStuck::these(Stoppages::in($envelope));
        } catch (RequestFailed $why) {
            return WhatIsStuck::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|StuckIsUnreadable|ALimitSaysNothing) {
            return WhatIsStuck::met(Obstacle::StackDidNotAnswer);
        }
    }
}
