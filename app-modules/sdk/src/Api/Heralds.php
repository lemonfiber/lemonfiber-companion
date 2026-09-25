<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\WhatTheAlertsWere;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what its operator is told about.
 *
 * Beside {@see Lookouts} and written the same way, with {@see Recorders}'
 * collapse of four raises into two answers. It asks and never changes: the
 * reading half of the endpoint and only that half.
 */
final readonly class Heralds implements Telling
{
    public function __construct(private Clients $clients) {}

    public function toldAbout(Stack $stack, Session $session): WhatTheAlertsWere
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::ALERTS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheAlertsWere::told(WhatIsTold::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheAlertsWere::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|AlertsAreUnreadable) {
            return WhatTheAlertsWere::met(Obstacle::StackDidNotAnswer);
        }
    }
}
