<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatWasFoundOfTheMembers;
use Modules\Sdk\Internal\WhatAnInvitationAsksWith;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack to let somebody in, and who is in already.
 *
 * Built the way {@see Menders} is: the client is fetched per stack and session,
 * each action is asked through {@see Api::action()} by a name
 * {@see AskingThemIn} spells, and the stack answers each with a handle that
 * {@see self::whatBecameOf()} follows to the invitation.
 *
 * **The yes carries a key; the rehearsal does not**, for {@see Menders}' reason:
 * a key names an attempt at changing a stack, and asking what an invitation
 * would come to changes nothing. Inviting and taking a password off both do.
 *
 * **A refusal keeps the stack's sentence.** The stack turns a request down —
 * a library it does not have, a name it cannot find, the account it signs in
 * with — at a status saying the fault is in the asking or the naming, and says
 * why in its `error` envelope. That sentence is the operator's answer, and it
 * is handed on as a refusal rather than folded into *the stack did not answer*.
 * A refused session stays the obstacle {@see WhatARefusalMeant} names, and a
 * fault on the stack's side, with no sentence to hand on, is the stack not
 * answering.
 *
 * **`NoSuchJob` is the stack having no outcome for the work**, which is
 * {@see WhatBecameOfTheInvitation::ended()} for {@see Menders}' reason.
 */
final readonly class Ushers implements Inviting
{
    /** The first status a stack answers a request it could not carry out with, rather than one it refused. */
    private const int THE_STACK_ITSELF_FAILED = 500;

    /** The first status that is a refusal at all. */
    private const int A_REFUSAL = 400;

    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function whoIsIn(Stack $stack, Session $session): WhatWasFoundOfTheMembers
    {
        try {
            // The household is read where the operator's requests are, and
            // for its people: the same answer, and one way of asking for it.
            $envelope = $this->clients->client($stack, $session)->read(Api::REQUESTS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheMembers::found(Households::whoIsIn($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfTheMembers::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable|InvitationSaysNothing) {
            return WhatWasFoundOfTheMembers::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function wouldInvite(Stack $stack, Session $session, AnInvitationAskedFor $asked): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(AskingThemIn::Invite->asked()),
                WhatAnInvitationAsksWith::offering($asked)->said,
            ));
        } catch (RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function invite(Stack $stack, Session $session, AnInvitationAgreed $agreed): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(AskingThemIn::Invite->asked()),
                WhatAnInvitationAsksWith::agreeing($agreed->asked())->said,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function takeThePasswordOff(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(AskingThemIn::TakeThePasswordOff->asked()),
                [WireField::Name->value => $who->name()],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheInvitation
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|InvitationIsUnreadable|InvitationSaysNothing) {
            return WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught.
     *
     * Apart from {@see self::whatBecameOf()} for {@see Menders::standing()}'s
     * reason: a name the stack does not recognise is its answer that it has no
     * outcome, and belongs with the states rather than with the failures.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheInvitation
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheInvitation => WhatBecameOfTheInvitation::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheInvitation
                    => WhatBecameOfTheInvitation::answered(Invitations::in($envelope)),
                ended: static fn(): WhatBecameOfTheInvitation => WhatBecameOfTheInvitation::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheInvitation::ended();
        }
    }

    /**
     * The handle an action was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheInvitation
    {
        try {
            return WhatBecameOfTheInvitation::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, or an obstacle.
     *
     * A refused session and an account that may not ask are obstacles with
     * remedies of their own. Anything else turned down in the asking or the
     * naming, with a sentence, is the stack's refusal, and that sentence is
     * the answer.
     */
    private function refusal(RequestFailed $why): WhatBecameOfTheInvitation
    {
        $obstacle = WhatARefusalMeant::obstacle($why);
        $said = $why->said();

        if ($obstacle !== Obstacle::StackDidNotAnswer || $said === null) {
            return WhatBecameOfTheInvitation::met($obstacle);
        }

        return $why->status() >= self::A_REFUSAL && $why->status() < self::THE_STACK_ITSELF_FAILED
            ? WhatBecameOfTheInvitation::refused($said)
            : WhatBecameOfTheInvitation::met($obstacle);
    }
}
