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
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheLineWasFound;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how it shares its line.
 *
 * Beside {@see Heralds} and written the same way, with {@see Recorders}'
 * collapse of four raises into two answers. A line that could not be read is
 * an obstacle, never an unlimited line.
 */
final readonly class Quartermasters implements Rationing
{
    public function __construct(private Clients $clients) {}

    public function rationedOn(Stack $stack, Session $session): WhatTheLineWasFound
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::BANDWIDTH_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheLineWasFound::shared(HowTheLineIs::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheLineWasFound::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|BandwidthIsUnreadable) {
            return WhatTheLineWasFound::met(Obstacle::StackDidNotAnswer);
        }
    }
}
