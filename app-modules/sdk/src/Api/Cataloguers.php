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
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheCatalogueSaid;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what each of its services is for.
 *
 * Beside {@see Archivists} and written the same way: it asks {@see Clients}
 * for the connection rather than building one, which keeps the certificate pin
 * in a single file.
 *
 * **A catalogue that could not be read is never an empty one.** Both would
 * draw the same blank screen, and only one of them is an answer.
 *
 * **A stack that cannot read its own description says so.** The catalogue is
 * read from the stack's manifest, and a manifest missing, malformed, written
 * for another version or naming things this build does not know is answered
 * with the problem document, which {@see WhatARefusalMeant::inItsWords()}
 * carries as the stack's refusal in its own words.
 */
final readonly class Cataloguers implements Cataloguing
{
    public function __construct(private Clients $clients) {}

    public function describedOn(Stack $stack, Session $session): WhatTheCatalogueSaid
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::CATALOGUE_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheCatalogueSaid::catalogue(Catalogues::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: WhatTheCatalogueSaid::refused(...),
                met: WhatTheCatalogueSaid::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|CatalogueIsUnreadable $why) {
            return WhatTheCatalogueSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
