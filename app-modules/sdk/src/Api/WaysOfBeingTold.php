<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\NotifyChooseAction;
use Modules\Kernel\Api\AWayToBeToldSaysNothing;
use Modules\Kernel\Api\ChoosingHowTheyAreTold;
use Modules\Kernel\Api\HowBeingToldCameBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/** Which ways a member is told on, read from `/api/notify/mine` and chosen with `notify-choose`, on the member's own session. */
final readonly class WaysOfBeingTold implements ChoosingHowTheyAreTold
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function offered(Stack $stack, Session $session): HowBeingToldCameBack
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowBeingToldCameBack::told(HowBeingToldRead::in($client->read(Api::NOTIFY_MINE_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowBeingToldCameBack::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|BeingToldIsUnreadable|AWayToBeToldSaysNothing $why) {
            return HowBeingToldCameBack::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function choose(Stack $stack, Session $session, array $ways): HowBeingToldCameBack
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowBeingToldCameBack::told(HowBeingToldRead::in($client->act(new NotifyChooseAction($ways))));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowBeingToldCameBack::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|ConfigurationProblem|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|BeingToldIsUnreadable|AWayToBeToldSaysNothing $why) {
            return HowBeingToldCameBack::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
