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
use Modules\Kernel\Api\ARestartSaysTooLittle;
use Modules\Kernel\Api\AskingHowItCameBack;
use Modules\Kernel\Api\HowItCameBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack when its machine last started and what the check after it came to.
 *
 * Beside {@see Heralds} and written the same way: it asks and never changes.
 */
final readonly class Restarts implements AskingHowItCameBack
{
    public function __construct(private Clients $clients) {}

    public function afterTheLastStart(Stack $stack, Session $session): HowItCameBack
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowItCameBack::said(WhatTheStartCameTo::in($client->read(Api::BOOT_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowItCameBack::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|StartIsUnreadable|ARestartSaysTooLittle $why) {
            return HowItCameBack::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
