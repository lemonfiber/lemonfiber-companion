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
use Modules\Kernel\Api\AskingWhatAStopWouldInterrupt;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\InFlightSaysNothing;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStopReaches;
use Modules\Kernel\Api\WhatWasFoundComingDown;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what is still coming down inside what an action would stop. It asks and never changes.
 */
final readonly class Forewarners implements AskingWhatAStopWouldInterrupt
{
    public function __construct(private Clients $clients) {}

    public function inside(Stack $stack, Session $session, WhatAStopReaches $reach): WhatWasFoundComingDown
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasFoundComingDown::said(TheInFlightRead::in($client->read(Api::IN_FLIGHT_ENDPOINT, [
                WireField::Forms->value => $this->named($reach->forms()),
                WireField::Services->value => $this->named($reach->services()),
            ])));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundComingDown::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|InFlightIsUnreadable|InFlightSaysNothing $why) {
            return WhatWasFoundComingDown::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * The names the stack is asked with, in order.
     *
     * @return list<string>
     */
    private function named(Forms|Services $reached): array
    {
        $names = [];

        foreach ($reached as $one) {
            $names[] = $one->named();
        }

        return $names;
    }
}
