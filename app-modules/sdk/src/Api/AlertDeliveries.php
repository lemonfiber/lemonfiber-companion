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
use Modules\Kernel\Api\KnowingWhereAlertsGo;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhereAlertsGoCameBack;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/** Where a machine's alerts go, read from its `notify` envelope. */
final readonly class AlertDeliveries implements KnowingWhereAlertsGo
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function whereAlertsGoOn(Stack $stack, Session $session): WhereAlertsGoCameBack
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhereAlertsGoCameBack::read(HowWhereAlertsGoRead::in($client->read(Api::NOTIFY_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhereAlertsGoCameBack::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|WhereAlertsGoIsUnreadable $why) {
            return WhereAlertsGoCameBack::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
