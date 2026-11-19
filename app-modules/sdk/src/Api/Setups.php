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
use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaidOfTheSetup;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what setup settled. It asks and never changes.
 */
final readonly class Setups implements AskingWhatSetupSettled
{
    public function __construct(private Clients $clients) {}

    public function settled(Stack $stack, Session $session): WhatWasSaidOfTheSetup
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasSaidOfTheSetup::said(TheSettledRead::in($client->read(Api::SETTLED_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasSaidOfTheSetup::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SettledIsUnreadable $why) {
            return WhatWasSaidOfTheSetup::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
