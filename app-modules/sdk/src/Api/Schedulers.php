<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RunAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Kernel\Api\WhatRunsOnASchedule;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\TheWorkAsked;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack about its run-and-exit services, and runs one at once through the `run` action with `now`.
 */
final readonly class Schedulers implements RunningOnASchedule
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

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

    /**
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function runNow(Stack $stack, Session $session, ServiceId $service): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, new RunAction(service: $service->named(), now: true));
    }

    /**
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): WhatRunningItNowCameTo => TheRunRead::in($envelope));
        } catch (ScheduleIsUnreadable|InstantIsBeforeTheEpoch $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
