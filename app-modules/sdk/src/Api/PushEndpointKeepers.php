<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\PushEndpointRegistering;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatRegisteringAWakeCameTo;
use Modules\Kernel\Api\WhatRemovingAWakeCameTo;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * The one place this application asks a stack to wake this phone, and to stop.
 *
 * A refusal is said in the stack's words. A removal of a name the stack holds
 * no endpoint under has nothing left to remove, and is read as done.
 */
final readonly class PushEndpointKeepers implements RegisteringAWake
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function registeredOn(Stack $stack, Session $session, AWakeToRegister $wake): WhatRegisteringAWakeCameTo
    {
        $asked = PushEndpointRegistering::of($wake->name->shown(), $wake->endpoint->address(), $wake->key->bytes(), $wake->secret->bytes());

        try {
            return WhatRegisteringAWakeCameTo::registered(PushEndpointRegistered::in(GatedClient::of($this->clients, $stack, $session)->pushEndpoints()->register($asked)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: static fn(ARefusalInItsWords $said): WhatRegisteringAWakeCameTo => WhatRegisteringAWakeCameTo::refused($said),
                met: static fn(Obstacle $met): WhatRegisteringAWakeCameTo => WhatRegisteringAWakeCameTo::met($met),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PushEndpointIsUnreadable $why) {
            return WhatRegisteringAWakeCameTo::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function removedFrom(Stack $stack, Session $session, APushName $name): WhatRemovingAWakeCameTo
    {
        try {
            PushEndpointRemoved::in(GatedClient::of($this->clients, $stack, $session)->pushEndpoints()->remove($name->shown()));

            return WhatRemovingAWakeCameTo::removed();
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusedRemoving($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PushEndpointIsUnreadable $why) {
            return WhatRemovingAWakeCameTo::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** A removal the stack turned down: done where it holds no endpoint by that name, said in its words or met otherwise. */
    private function refusedRemoving(CertificateWasRefused|RequestFailed $why): WhatRemovingAWakeCameTo
    {
        if ($why instanceof RequestFailed && $why->code() === RefusalCode::PushNotRegistered) {
            return WhatRemovingAWakeCameTo::removed();
        }

        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatRemovingAWakeCameTo => WhatRemovingAWakeCameTo::refused($said),
            met: static fn(Obstacle $met): WhatRemovingAWakeCameTo => WhatRemovingAWakeCameTo::met($met),
        );
    }
}
