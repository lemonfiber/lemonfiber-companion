<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Logs;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaid;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application reads what a service has been saying.
 *
 * `N1-R16` says every call goes through the SDK, so this sits beside
 * {@see Stalls} and is written the same way: it asks {@see PinnedClients} for
 * the connection rather than building one, which is what keeps the certificate
 * pin in a single file. This class never names a client constructor, so it
 * cannot make a decision about whether a certificate is checked.
 *
 * **A `ConfigurationProblem` is caught here and nowhere else in this module**,
 * and the placement is the whole of why that is not a contradiction.
 * {@see PinnedClients::client()} is called *outside* the `try`, so the one
 * {@see Stalls} refuses to catch — a stored stack that cannot be pinned, which
 * means this app is holding a stack it should never have written down — still
 * escapes. What is inside is `Logs::ofService()`, which raises the same class
 * for a blank service or a window of no lines. The kernel's own types refuse
 * both before the call is made, so a raise from there means the two ends
 * disagree about what a bound is: a machine that moved, not retained state that
 * is wrong, and an obstacle is the honest screen for it.
 *
 * **Five raises, two answers**, which is {@see Stalls}' collapse widened by one:
 * the endpoint refusing, the answer being unreadable, the two ends disagreeing
 * about the API version and a window this side could not read are four faults
 * with one meaning for somebody holding a phone — they cannot see what the
 * service said, and the machine is where to look. The exception is a refused
 * session, which is a different sentence and is told apart by
 * {@see WhatARefusalMeant}.
 */
final readonly class Scrollbacks implements Saying
{
    public function __construct(private PinnedClients $clients) {}

    public function saidBy(Stack $stack, Session $session, ServiceId $service, HowManyLines $lines): WhatWasSaid
    {
        $client = $this->clients->client($stack, $session);

        try {
            $window = $client->logs(Logs::ofService($service->named(), $lines->figure()));

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A window the client fetched and
            // this side could not read is the same thing to an operator as one
            // that never arrived.
            return WhatWasSaid::this(Lines::in($window));
        } catch (RequestFailed $why) {
            return WhatWasSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|ConfigurationProblem|LineIsUnreadable) {
            return WhatWasSaid::met(Obstacle::StackDidNotAnswer);
        }
    }
}
