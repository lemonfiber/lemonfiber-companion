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
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatRunsOnASchedule;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack about its run-and-exit services.
 */
final readonly class Schedulers implements RunningOnASchedule
{
    public function __construct(private Clients $clients) {}

    public function scheduled(Stack $stack, Session $session): WhatRunsOnASchedule
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatRunsOnASchedule::said(TheScheduleRead::in($client->read(Api::STATUS_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatRunsOnASchedule::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|ScheduleIsUnreadable|InstantIsBeforeTheEpoch|ServiceIsUnnamed $why) {
            return WhatRunsOnASchedule::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
