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
use Lemonfiber\Sdk\Generated\InviteAction;
use Lemonfiber\Sdk\Generated\ReissueAction;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatWasFoundOfTheMembers;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatStoodInTheWayOfTheHousehold;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * The one place this application asks a stack to let somebody in, and who is in already.
 *
 * Built the way {@see Menders} is: the client is fetched per stack and session,
 * each action is asked as the class the SDK generates for it, and the stack
 * answers each with a handle that {@see self::whatBecameOf()} follows to the
 * invitation.
 *
 * **Each asking carries a key of its own**, the rehearsal included, for
 * {@see Restorers}' reason.
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
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function whoIsIn(Stack $stack, Session $session): WhatWasFoundOfTheMembers
    {
        try {
            // The household is read where the operator's requests are, and
            // for its people: the same answer, and one way of asking for it.
            $envelope = GatedClient::of($this->clients, $stack, $session)->read(Api::REQUESTS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheMembers::found(Households::whoIsIn($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheMembers::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HouseholdIsUnreadable|HouseholdWentUnread|InvitationSaysNothing $why) {
            return WhatWasFoundOfTheMembers::met(WhatStoodInTheWayOfTheHousehold::ofTheHousehold($this->clients, $stack, $why));
        }
    }

    public function wouldInvite(Stack $stack, Session $session, AnInvitationAskedFor $asked): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                $this->invitation($asked, agreed: false),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheInvitation::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function invite(Stack $stack, Session $session, AnInvitationAgreed $agreed): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                $this->invitation($agreed->asked(), agreed: true),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheInvitation::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function takeThePasswordOff(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheInvitation
    {
        try {
            return $this->underway(GatedClient::of($this->clients, $stack, $session)->act(
                new ReissueAction(name: $who->name()),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse $why) {
            return WhatBecameOfTheInvitation::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheInvitation
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|InvitationIsUnreadable|InvitationSaysNothing $why) {
            return WhatBecameOfTheInvitation::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * The invitation as the `invite` action is asked, with the yes or without it.
     *
     * `agreed` is named at both call sites, for {@see Adjustments}' reason: the
     * two lines deciding whether somebody gets an account read as
     * `agreed: false` and `agreed: true`. An age limit and a word about unrated
     * material carry a value only where one was said, read through the
     * kernel's folds, so nothing stands in for either.
     */
    private function invitation(AnInvitationAskedFor $asked, bool $agreed): InviteAction
    {
        // Collected by hand rather than through `iterator_to_array`, whose
        // `preserve_keys` argument would change nothing here.
        $libraries = [];

        foreach ($asked->libraries() as $library) {
            $libraries[] = $library;
        }

        $limited = $asked->age(
            upTo: static fn(int $age): InviteAction => new InviteAction(name: $asked->name(), libraries: $libraries, ageLimit: $age, confirm: $agreed),
            none: static fn(): InviteAction => new InviteAction(name: $asked->name(), libraries: $libraries, confirm: $agreed),
        );

        return $asked->unrated(
            chosen: static fn(WhatBecomesOfUnrated $unrated): InviteAction => new InviteAction(
                name: $limited->name,
                libraries: $limited->libraries,
                ageLimit: $limited->ageLimit,
                unrated: $unrated->asked(),
                confirm: $limited->confirm,
            ),
            unsaid: static fn(): InviteAction => $limited,
        );
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
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
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
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheInvitation::met(WhatTheReachMet::byItself($why));
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, where
     * {@see WhatARefusalMeant::inItsOwnWords()} finds one, or an obstacle.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheInvitation
    {
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheInvitation::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheInvitation::refused($said);
    }
}
