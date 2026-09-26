<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Measuring;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasMeasured;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how full its machine is.
 *
 * Written the way {@see Storekeepers} is: it asks {@see Clients} for the
 * connection rather than building one, which keeps the certificate pin in a
 * single file.
 *
 * **What could not be read is never a comfortable disk.** A reading this app
 * cannot read is an obstacle, drawn as one.
 */
final readonly class Surveyors implements Measuring
{
    public function __construct(private Clients $clients) {}

    public function measuredOn(Stack $stack, Session $session): WhatWasMeasured
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::SPACE_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasMeasured::measured(WhereTheRoomIs::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasMeasured::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|SpaceIsUnreadable) {
            return WhatWasMeasured::met(Obstacle::StackDidNotAnswer);
        }
    }
}
