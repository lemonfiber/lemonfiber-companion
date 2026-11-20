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
use Modules\Kernel\Api\AskingHowItIsServed;
use Modules\Kernel\Api\CertificateSaysTooMuch;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaidOfServing;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how it serves its web surface. It asks and never changes.
 */
final readonly class Servings implements AskingHowItIsServed
{
    public function __construct(private Clients $clients) {}

    public function served(Stack $stack, Session $session): WhatWasSaidOfServing
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasSaidOfServing::said(TheServedRead::in($client->read(Api::SERVED_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasSaidOfServing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|ServedIsUnreadable|CertificateSaysTooMuch|FingerprintIsNotAFingerprint $why) {
            return WhatWasSaidOfServing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
