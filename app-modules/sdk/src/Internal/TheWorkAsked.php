<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Closure;
use Lemonfiber\Sdk\ActionRequest;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\HandleIsUnreadable;
use Modules\Sdk\Api\Handles;
use Modules\Sdk\Api\TheStackDoesNotOfferIt;

/**
 * Work asked of a stack through the SDK, sent under its own idempotency key and followed by the handle it answered.
 *
 * What finished work answers with is the caller's to read, and a reading it cannot make is the caller's to catch.
 * Given the code a stack refuses a yes with when what it was given for has
 * moved, that refusal answers as moved rather than as refused.
 */
final readonly class TheWorkAsked
{
    public function __construct(private Clients $clients, private Entropy $entropy, private ?RefusalCode $moved = null) {}

    /**
     * Send the action: the handle the stack answered, its refusal in its own words, or what stood in the way.
     *
     * @return WhatBecameOfTheWork<never>
     */
    public function sent(Stack $stack, Session $session, ActionRequest $action): WhatBecameOfTheWork
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
     * Follow the work by its handle, reading what it finished with through `$read`.
     *
     * @template TAnswer of object
     *
     * @param Closure(Envelope<mixed>): TAnswer $read
     *
     * @return WhatBecameOfTheWork<TAnswer>
     */
    public function followed(Stack $stack, Session $session, Job $job, Closure $read): WhatBecameOfTheWork
    {
        try {
            return $this->standing($stack, $session, $job, $read);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for {@see \Modules\Sdk\Api\Ushers}'s reason: it is a refusal on the wire, and here it means the stack has no outcome any more.
     *
     * @template TAnswer of object
     *
     * @param Closure(Envelope<mixed>): TAnswer $read
     *
     * @return WhatBecameOfTheWork<TAnswer>
     */
    private function standing(Stack $stack, Session $session, Job $job, Closure $read): WhatBecameOfTheWork
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheWork => WhatBecameOfTheWork::answered($read($envelope)),
                ended: static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheWork::ended();
        }
    }

    /**
     * @param Envelope<mixed> $envelope
     *
     * @return WhatBecameOfTheWork<never>
     */
    private function underway(Envelope $envelope): WhatBecameOfTheWork
    {
        try {
            return WhatBecameOfTheWork::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheWork::met(WhatTheReachMet::byItself($why));
        }
    }

    /** @return WhatBecameOfTheWork<never> */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheWork
    {
        $moved = $why instanceof RequestFailed && $this->moved instanceof RefusalCode && $why->code() === $this->moved;

        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfTheWork => $moved ? WhatBecameOfTheWork::moved($said) : WhatBecameOfTheWork::refused($said),
            met: static fn(Obstacle $met): WhatBecameOfTheWork => WhatBecameOfTheWork::met($met),
        );
    }
}
