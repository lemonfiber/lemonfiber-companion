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
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\WhatWasFoundKept;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it keeps on its machine.
 *
 * Beside {@see Archivists} and written the same way: it asks {@see Clients} for
 * the connection rather than building one, which keeps the certificate pin in
 * a single file. Four raises, two answers, and a refused session told apart by
 * {@see WhatARefusalMeant} because it is a different sentence.
 *
 * **What could not be read is never an empty list.** Both would draw a machine
 * that keeps nothing, and only one of them is an answer.
 */
final readonly class Storekeepers implements Storing
{
    public function __construct(private Clients $clients) {}

    public function storedOn(Stack $stack, Session $session): WhatWasFoundKept
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::STORED_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundKept::kept(WhatIsStored::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundKept::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|StoredIsUnreadable $why) {
            return WhatWasFoundKept::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
