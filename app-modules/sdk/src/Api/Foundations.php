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
use Modules\Kernel\Api\AskingHowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaidOfRunningWithout;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how it runs without lemonfiber.
 *
 * Beside {@see Watchers} and written the same way: it asks and never changes.
 */
final readonly class Foundations implements AskingHowItRunsWithoutLemonfiber
{
    public function __construct(private Clients $clients) {}

    public function withoutLemonfiber(Stack $stack, Session $session): WhatWasSaidOfRunningWithout
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasSaidOfRunningWithout::said(WhatTheStackSaidOfItself::in($client->read(Api::STACK_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasSaidOfRunningWithout::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|StackIsUnreadable $why) {
            return WhatWasSaidOfRunningWithout::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
