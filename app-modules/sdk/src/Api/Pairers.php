<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\MakingPairingCodes;
use Modules\Kernel\Api\PairingIsNotReadable;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;
use Modules\Kernel\Api\WhatToDoAboutPairing;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * The one place this application asks a stack for a pairing code, and follows it.
 *
 * Built the way {@see Ushers} is: the stack answers the asking with a handle,
 * and the code arrives through it. A stack that turns it down — not served
 * encrypted on the network, no address a phone could reach — says why in its
 * own words, and that sentence is handed on as the refusal.
 *
 * **Each asking carries a key of its own**, for {@see MakingPairingCodes}' reason.
 */
final readonly class Pairers implements MakingPairingCodes
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function make(Stack $stack, Session $session): WhatBecameOfThePairingCode
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(WhatToDoAboutPairing::MakeACode->asked()),
                [],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse $why) {
            return WhatBecameOfThePairingCode::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfThePairingCode
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PairingIsUnreadable|PairingIsNotReadable $why) {
            return WhatBecameOfThePairingCode::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for
     * {@see Ushers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfThePairingCode
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfThePairingCode => WhatBecameOfThePairingCode::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfThePairingCode
                    => WhatBecameOfThePairingCode::made(PairingCodes::in($envelope)),
                ended: static fn(): WhatBecameOfThePairingCode => WhatBecameOfThePairingCode::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfThePairingCode::ended();
        }
    }

    /**
     * The handle the asking was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfThePairingCode
    {
        try {
            return WhatBecameOfThePairingCode::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfThePairingCode::met(WhatTheReachMet::byItself($why));
        }
    }

    /** What a request the stack turned down means: its own sentence, or an obstacle. */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfThePairingCode
    {
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfThePairingCode::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfThePairingCode::refused($said);
    }
}
