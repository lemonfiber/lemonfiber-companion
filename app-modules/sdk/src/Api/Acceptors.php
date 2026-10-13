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
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * Accepting a warning through the SDK: the `accept` action for one check, and following it to the report it answers with.
 */
final readonly class Acceptors implements Accepting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheWork
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                new AcceptAction(check: $check->shown()),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ReportIsUnreadable|CheckIsUnnamed|ServiceIsUnnamed|FindingHasNoTitle|CodeIsBlank|CheckSaidNothing|RemedySaysNothing|CheckGaveNoReason $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see Ushers::outcome()}'s reason.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheWork => WhatBecameOfTheWork::answered(Reports::in($envelope)),
                ended: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheWork::ended();
        }
    }

    /**
     * @param Envelope<mixed> $envelope
     * @return WhatBecameOfTheWork<Report>
     */
    private function underway(Envelope $envelope): WhatBecameOfTheWork
    {
        try {
            return WhatBecameOfTheWork::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheWork::met(WhatTheReachMet::byItself($why));
        }
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheWork
    {
        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfTheWork => WhatBecameOfTheWork::refused($said),
            met: static fn(Obstacle $met): WhatBecameOfTheWork => WhatBecameOfTheWork::met($met),
        );
    }
}
