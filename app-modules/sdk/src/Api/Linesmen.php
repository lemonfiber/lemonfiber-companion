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
use Lemonfiber\Sdk\Generated\BandwidthAction;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SharingTheLine;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheLineChange;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * Changing how a stack shares its line through the SDK: the `bandwidth` action, rehearsed, carried out, and followed.
 *
 * Every part of the change is sent as the operator wrote it and read in the
 * core. A refusal is the stack's own sentence and remedy where it sent a
 * problem document, and an obstacle everywhere else.
 */
final readonly class Linesmen implements SharingTheLine
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange
    {
        return $this->sent($stack, $session, $this->action($change)->rehearsed());
    }

    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange
    {
        return $this->sent($stack, $session, $this->action($change));
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheLineChange
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|BandwidthIsUnreadable|LineSaysNothing $why) {
            return WhatBecameOfTheLineChange::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function action(ALineChange $change): BandwidthAction
    {
        $written = [];

        foreach ($change as $part) {
            $written[$part->part()->value] = $part->text();
        }

        return $change->lift()->either(
            for: static fn(int $minutes): BandwidthAction => new BandwidthAction(...$written, unrestrictedFor: $minutes),
            none: static fn(): BandwidthAction => new BandwidthAction(...$written),
        );
    }

    private function sent(Stack $stack, Session $session, BandwidthAction $action): WhatBecameOfTheLineChange
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                $action,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheLineChange::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see Ushers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheLineChange
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::answered(WhatTheLineCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheLineChange::ended();
        }
    }

    /**
     * The handle an action was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheLineChange
    {
        try {
            return WhatBecameOfTheLineChange::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheLineChange::met(WhatTheReachMet::byItself($why));
        }
    }

    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheLineChange
    {
        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::refused($said),
            met: static fn(Obstacle $met): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::met($met),
        );
    }
}
