<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\ForgetAction;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Forgetting;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfForgetting;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * Removing everything lemonfiber keeps through the SDK: the `forget` action without the yes, then with it, and followed.
 */
final readonly class Forgetters implements Forgetting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function whatWouldGo(Stack $stack, Session $session): WhatBecameOfForgetting
    {
        return $this->sent($stack, $session, new ForgetAction());
    }

    public function forget(Stack $stack, Session $session, ForgettingAgreed $agreed): WhatBecameOfForgetting
    {
        return $this->sent($stack, $session, new ForgetAction(confirm: true));
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfForgetting
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|StoredIsUnreadable $why) {
            return WhatBecameOfForgetting::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function sent(Stack $stack, Session $session, ForgetAction $action): WhatBecameOfForgetting
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                $action,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfForgetting::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see Ushers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfForgetting
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfForgetting => WhatBecameOfForgetting::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfForgetting => WhatBecameOfForgetting::answered(WhatForgettingCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfForgetting => WhatBecameOfForgetting::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfForgetting::ended();
        }
    }

    /** @param Envelope<mixed> $envelope */
    private function underway(Envelope $envelope): WhatBecameOfForgetting
    {
        try {
            return WhatBecameOfForgetting::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfForgetting::met(WhatTheReachMet::byItself($why));
        }
    }

    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfForgetting
    {
        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfForgetting => WhatBecameOfForgetting::refused($said),
            met: static fn(Obstacle $met): WhatBecameOfForgetting => WhatBecameOfForgetting::met($met),
        );
    }
}
