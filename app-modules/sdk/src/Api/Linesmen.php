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
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
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

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork
    {
        return $this->sent($stack, $session, $this->action($change)->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork
    {
        return $this->sent($stack, $session, $this->action($change));
    }

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|BandwidthIsUnreadable|LineSaysNothing $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
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

    /**
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    private function sent(Stack $stack, Session $session, BandwidthAction $action): WhatBecameOfTheWork
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
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheWork => WhatBecameOfTheWork::answered(WhatTheLineCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheWork::ended();
        }
    }

    /**
     * The handle an action was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     * @return WhatBecameOfTheWork<TheLineAsChanged>
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
     * @return WhatBecameOfTheWork<TheLineAsChanged>
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
