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
use Lemonfiber\Sdk\Generated\HouseholdAllowAction;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * Choosing what the household may ask for through the SDK: the `household-allow` action, rehearsed, carried out, and followed.
 *
 * Only what the operator named is sent, so what was not named stays as the stack holds it.
 */
final readonly class Allowers implements ChoosingWhatTheHouseholdMayAsk
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        return $this->sent($stack, $session, $this->action($change)->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        return $this->sent($stack, $session, $this->action($change));
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function action(AnAllowanceChange $change): HouseholdAllowAction
    {
        $named = $change->for()->either(
            everybody: static fn(): HouseholdAllowAction => new HouseholdAllowAction(),
            somebody: static fn(SomebodyInTheHousehold $who): HouseholdAllowAction => new HouseholdAllowAction(name: $who->name()),
        );

        $chosen = $change->policy(
            chosen: static fn(HowTheHouseholdAsks $policy): HouseholdAllowAction => new HouseholdAllowAction(name: $named->name, policy: $policy->value),
            asItIs: static fn(): HouseholdAllowAction => $named,
        );

        return $change->limit(
            chosen: static fn(ARequestLimit $limit): HouseholdAllowAction => new HouseholdAllowAction(
                name: $chosen->name,
                policy: $chosen->policy,
                requests: $limit->requests(),
                days: $limit->days(),
            ),
            asItIs: static fn(): HouseholdAllowAction => $chosen,
        );
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    private function sent(Stack $stack, Session $session, HouseholdAllowAction $action): WhatBecameOfTheWork
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                $action,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see Ushers::outcome()}'s reason.
     *
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheWork => WhatBecameOfTheWork::answered(WhatTheAllowanceCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheWork::ended();
        }
    }

    /**
     * @param Envelope<mixed> $envelope
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
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
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
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
