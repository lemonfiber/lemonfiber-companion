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
use Lemonfiber\Sdk\Generated\AcceptAction;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\CheckGaveNoReason;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\CheckSaidNothing;
use Modules\Kernel\Api\CodeIsBlank;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FindingHasNoTitle;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheAcceptance;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * Accepting a warning through the SDK: the `accept` action for one check, and following it to the report it answers with.
 */
final readonly class Acceptors implements Accepting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheAcceptance
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                new AcceptAction(check: $check->shown()),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheAcceptance::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheAcceptance
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ReportIsUnreadable|CheckIsUnnamed|ServiceIsUnnamed|FindingHasNoTitle|CodeIsBlank|CheckSaidNothing|RemedySaysNothing|CheckGaveNoReason $why) {
            return WhatBecameOfTheAcceptance::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see Ushers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheAcceptance
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::answered(Reports::in($envelope)),
                ended: static fn(): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheAcceptance::ended();
        }
    }

    /** @param Envelope<mixed> $envelope */
    private function underway(Envelope $envelope): WhatBecameOfTheAcceptance
    {
        try {
            return WhatBecameOfTheAcceptance::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheAcceptance::met(WhatTheReachMet::byItself($why));
        }
    }

    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheAcceptance
    {
        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::refused($said),
            met: static fn(Obstacle $met): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::met($met),
        );
    }
}
