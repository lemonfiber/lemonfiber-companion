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
use Modules\Kernel\Api\Wanting;
use Modules\Kernel\Api\WhatWasWanted;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what the house wants.
 *
 * `N1-R16` says every call goes through the SDK, so this sits beside
 * {@see Questions} and is written the same way: it asks {@see PinnedClients}
 * for the connection rather than building one, which is what keeps the
 * certificate pin in a single file. This class never names a client
 * constructor, so it cannot make a decision about whether a certificate is
 * checked.
 *
 * **It asks for the whole household rather than for one member.** The endpoint
 * takes a `member` parameter and this does not offer it — `N1-R65` has a screen
 * read once per frame and render what came back, and narrowing is a question for whoever
 * holds the answer rather than another trip to a machine on a home network.
 *
 * **Four raises, two answers**, which is {@see Questions}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone — they cannot see what their house asked for, and
 * the machine is where to look. The exception is a refused session, which is a
 * different sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Admissions}'
 * reason: the SDK raises one where a stored stack cannot be pinned, which means
 * this app is holding a stack it should never have written down. Catching it
 * would turn a fault in retained state into an ordinary screen about an
 * unreachable machine.
 */
final readonly class Requests implements Wanting
{
    public function __construct(private Clients $clients) {}

    public function askedOf(Stack $stack, Session $session): WhatWasWanted
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::REQUESTS_ENDPOINT);

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Questions::about()} makes. A payload the client fetched and
            // this side could not read is the same thing to an operator as one
            // that never arrived, and reading it outside would put an uncaught
            // raise on a screen instead.
            return WhatWasWanted::these(Households::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasWanted::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable) {
            return WhatWasWanted::met(Obstacle::StackDidNotAnswer);
        }
    }
}
