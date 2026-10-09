<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\GrantAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\WhatTheGrantCameTo;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see Granting}, answered by asking the stack.
 *
 * The `grant` action answers at once with the `grant` envelope, under the
 * session that asked: a member's request is narrowed to them by the core, so
 * no member is named here.
 */
final readonly class Grantors implements Granting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function aGrantFor(Stack $stack, Session $session, ThisDevice $device): WhatTheGrantCameTo
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                new GrantAction(device: $device->shown()),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return WhatTheGrantCameTo::granted(Grants::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheGrantCameTo::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|GrantIsUnreadable $why) {
            return WhatTheGrantCameTo::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
