<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\WatchedAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HoldingIsUnnamed;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\KeepingThePlace;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\WhatThePlaceCameTo;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see KeepingThePlace}, answered by telling the stack.
 *
 * The `watched` action answers at once with the `watched` envelope, under the
 * session that told it, so the place is kept as the member's own. Reaching the
 * end is said where the player reached it, and left out otherwise: absent says
 * nothing about finishing. What the core answers is the place it kept.
 */
final readonly class PlaceKeepers implements KeepingThePlace
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function keep(Stack $stack, Session $session, ThePlace $place): WhatThePlaceCameTo
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                new WatchedAction(
                    id: $place->holding()->named(),
                    position: $place->howFarIn()->seconds(),
                    ended: $place->isTheEnd() ? true : null,
                ),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return WhatThePlaceCameTo::kept(PlacesKept::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatThePlaceCameTo::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|PlaceIsUnreadable|HoldingIsUnnamed $why) {
            return WhatThePlaceCameTo::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
