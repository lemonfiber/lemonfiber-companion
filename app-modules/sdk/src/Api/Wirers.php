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
use Lemonfiber\Sdk\Generated\SeedAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheWiringSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WiringTheServices;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * {@see WiringTheServices}, answered by asking the stack.
 *
 * Written the way {@see Scouts} asks and follows a move: `seed` takes nothing,
 * answers with a handle under a fresh key, and {@see self::whatBecameOf()}
 * follows the handle to the `seed` envelope. A request the stack turned down
 * keeps its sentence; a refused session and a machine that is not the one
 * paired stay the obstacles {@see WhatARefusalMeant} names.
 */
final readonly class Wirers implements WiringTheServices
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function wire(Stack $stack, Session $session): WhatBecameOfTheWiring
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                new SeedAction(),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheWiring::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWiring
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|SeedIsUnreadable|TheWiringSaysNothing $why) {
            return WhatBecameOfTheWiring::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the run, with only `NoSuchJob` caught, for
     * {@see Menders::standing()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheWiring
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheWiring => WhatBecameOfTheWiring::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheWiring
                    => WhatBecameOfTheWiring::answered(WhatTheWiringCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfTheWiring => WhatBecameOfTheWiring::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheWiring::ended();
        }
    }

    /**
     * The handle the run was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheWiring
    {
        try {
            return WhatBecameOfTheWiring::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheWiring::met(WhatTheReachMet::byItself($why));
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, or an obstacle.
     *
     * Which of the two it is, is {@see WhatARefusalMeant::inItsOwnWords()}'s
     * to say.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheWiring
    {
        // A machine that is not the one paired never answered in words.
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheWiring::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheWiring::refused($said);
    }
}
