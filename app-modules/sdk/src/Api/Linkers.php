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
use Modules\Kernel\Api\Linking;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheLinksSaid;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it wires to what.
 *
 * Beside {@see Cataloguers} and written the same way: it asks {@see Clients}
 * for the connection rather than building one, which keeps the certificate pin
 * in a single file.
 *
 * **A wiring that could not be read is never an empty one.** The stack refuses
 * a record it cannot read with the problem document, which
 * {@see WhatARefusalMeant::inItsWords()} carries as the stack's refusal in its
 * own words; an envelope this app cannot read is an obstacle. Neither is drawn
 * as a stack that asks nothing of its services.
 */
final readonly class Linkers implements Linking
{
    public function __construct(private Clients $clients) {}

    public function linkedOn(Stack $stack, Session $session): WhatTheLinksSaid
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::WIRING_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheLinksSaid::links(Links::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: WhatTheLinksSaid::refused(...),
                met: WhatTheLinksSaid::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|LinksAreUnreadable $why) {
            return WhatTheLinksSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
