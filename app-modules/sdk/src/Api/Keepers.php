<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatKeepsRunning;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a machine what it keeps running.
 *
 * Every call to a stack goes through the SDK, so this sits beside
 * {@see Stalls} and is written the same way: it asks {@see PinnedClients} for
 * the connection rather than building one, which is what keeps the certificate
 * pin in a single file. This class never names a client constructor, so it
 * cannot make a decision about whether a certificate is checked.
 *
 * **The endpoint takes nothing**, so there is no filter to get wrong and no
 * narrowing a screen could be tempted into asking for row by row.
 *
 * **Four raises, two answers**, which is {@see Stalls}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone — they cannot see what this machine keeps running,
 * and the machine is where to look. The exception is a refused session, which
 * is a different sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A machine with no service manager is not one of the four.** It answers
 * normally, through the first arm, carrying the sentence saying what to do
 * instead — the reading a platform gave, not a failure to reach it. Folding it
 * in here would send an operator to check their network about a laptop that was
 * never going to run a launch agent.
 */
final readonly class Keepers implements Hosting
{
    public function __construct(private Clients $clients) {}

    public function keptRunningOn(Stack $stack, Session $session): WhatKeepsRunning
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::HOSTING_ENDPOINT);

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched
            // and this side could not read is the same thing to an operator as
            // one that never arrived, and reading it outside would put an
            // uncaught raise on a screen instead.
            return WhatKeepsRunning::keeps(Hosts::in($envelope));
        } catch (RequestFailed $why) {
            return WhatKeepsRunning::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HostingIsUnreadable) {
            return WhatKeepsRunning::met(Obstacle::StackDidNotAnswer);
        }
    }
}
