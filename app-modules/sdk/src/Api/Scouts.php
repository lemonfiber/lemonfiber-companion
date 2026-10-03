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
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheMoveSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheMove;
use Modules\Kernel\Api\WhatWasFoundAlreadyHere;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * The one place this application asks a stack what is already on its machine, and moves in beside it.
 *
 * Written the way {@see Doorkeepers} is, and for its reason: a survey that
 * could not be read is never a machine with nothing on it.
 *
 * **Each way of moving in is one action read twice**, as {@see Upgraders}'
 * is: without the yes it says what it would come to and does nothing, and
 * with it it is carried out. `confirm` is the yes, except to a replacement,
 * whose yes is `offer`: the name of the offer that said what it would stop,
 * from the stack's own answer without the yes. The stack answers both with a
 * handle, which {@see self::whatBecameOf()} follows
 * to where the move stands, as {@see Ushers} follows an invitation.
 *
 * **The yes carries a key; the question does not**, for {@see Menders}'
 * reason: a key names an attempt at changing a stack, and asking what a move
 * would come to changes nothing.
 *
 * **A refusal keeps the stack's sentence**, for {@see Ushers}' reason. A move
 * the stack turned away arrives answered and blocked, with its reason; a
 * request turned down before it was a move arrives as the stack's words. A
 * refused session and a machine that is not the one paired stay the
 * obstacles {@see WhatARefusalMeant} names.
 */
final readonly class Scouts implements MovingIn
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function surveyedOn(Stack $stack, Session $session): WhatWasFoundAlreadyHere
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::MIGRATION_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundAlreadyHere::found(WhatIsAlreadyHere::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundAlreadyHere::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|MigrationIsUnreadable $why) {
            return WhatWasFoundAlreadyHere::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function wouldMoveIn(Stack $stack, Session $session, MovingInBy $by): WhatBecameOfTheMove
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action($by->asked()),
                [UpdateField::Confirm->value => false],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse $why) {
            return WhatBecameOfTheMove::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function moveIn(Stack $stack, Session $session, AMoveAgreed $agreed): WhatBecameOfTheMove
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action($agreed->by()->asked()),
                $this->theYes($agreed),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse $why) {
            return WhatBecameOfTheMove::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheMove
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|MoveIsUnreadable|TheMoveSaysNothing $why) {
            return WhatBecameOfTheMove::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught.
     *
     * Apart from {@see self::whatBecameOf()} for {@see Menders::standing()}'s
     * reason: a name the stack does not recognise is its answer that it has no
     * outcome, and belongs with the states rather than with the failures.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheMove
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheMove => WhatBecameOfTheMove::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheMove
                    => WhatBecameOfTheMove::answered(WhatMovingInCameTo::in($envelope)),
                ended: static fn(): WhatBecameOfTheMove => WhatBecameOfTheMove::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheMove::ended();
        }
    }

    /**
     * The yes a move is sent with: the name of the offer where it was given one, a confirmation where not.
     *
     * @return array<string, bool|string>
     */
    private function theYes(AMoveAgreed $agreed): array
    {
        return $agreed->offer() === ''
            ? [UpdateField::Confirm->value => true]
            : [RestoreField::Offer->value => $agreed->offer()];
    }

    /**
     * The handle an act was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheMove
    {
        try {
            return WhatBecameOfTheMove::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheMove::met(WhatTheReachMet::byItself($why));
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, or an obstacle.
     *
     * Which of the two it is, is {@see WhatARefusalMeant::inItsOwnWords()}'s
     * to say, once for every adapter that hands a refusal on.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheMove
    {
        // A machine that is not the one paired never answered in words.
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheMove::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheMove::refused($said);
    }
}
