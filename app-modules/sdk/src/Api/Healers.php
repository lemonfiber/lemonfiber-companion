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
use Modules\Kernel\Api\AskingTheQueue;
use Modules\Kernel\Api\HowLongIsBelowNothing;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheQueueSaid;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack how its queue heals itself.
 *
 * Beside {@see Watchers} and written the same way: it asks and never acts.
 */
final readonly class Healers implements AskingTheQueue
{
    public function __construct(private Clients $clients) {}

    public function howItHeals(Stack $stack, Session $session): WhatTheQueueSaid
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatTheQueueSaid::said(WhatTheQueueSaw::in($client->read(Api::QUEUE_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheQueueSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|QueueIsUnreadable|HowLongIsBelowNothing|InstantIsBeforeTheEpoch|ServiceIsUnnamed $why) {
            return WhatTheQueueSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
