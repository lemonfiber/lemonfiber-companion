<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Provenance;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheOriginsWere;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack where its services come from.
 *
 * Beside {@see Recorders} and written the same way: it asks {@see Clients} for
 * the connection rather than building one, which keeps the certificate pin in
 * a single file.
 *
 * **Four raises, two answers**, which is {@see Recorders}' collapse for its
 * reason. A refused session is told apart by {@see WhatARefusalMeant},
 * because it is a different sentence.
 *
 * **Origins that could not be read are never an empty list.** Both would draw
 * the same blank screen, and only one of them is an answer.
 */
final readonly class Archivists implements Provenance
{
    public function __construct(private Clients $clients) {}

    public function declaredOn(Stack $stack, Session $session): WhatTheOriginsWere
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::PROVENANCE_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheOriginsWere::origins(Origins::in($envelope));
        } catch (RequestFailed $why) {
            return WhatTheOriginsWere::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ProvenanceIsUnreadable) {
            return WhatTheOriginsWere::met(Obstacle::StackDidNotAnswer);
        }
    }
}
