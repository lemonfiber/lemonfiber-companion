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
use Lemonfiber\Sdk\Generated\WatchAction;
use Lemonfiber\Sdk\JobStanding;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack to guard its data location, following the guard, and letting it go, through the SDK.
 *
 * Built as {@see Bundlers} is: the client is fetched per stack and session,
 * and what the stack answers is read inside the same `try` as the request.
 *
 * **Asking after the guard is what keeps it.** The stack holds a guard only
 * while somebody asks about it, so {@see whatBecameOf()} is a renewal as well
 * as a read, and {@see letGo()} releases it by its name.
 *
 * **A guard that could not start is the stack's answer, not a fault.** Where
 * there was no data location to guard, or it was already gone, asking after
 * the guard is answered with the refusal, and its sentence is carried as
 * {@see HowTheGuardIsGoing::refused()}. Only a refused session, an account
 * that may not ask, an answer with no sentence in it, and a machine that is
 * not the one paired are obstacles.
 *
 * **A name the stack never handed out in its current run is not an ending.**
 * Every other job here reads it as ended; a guard reads it as unknown, because
 * the stack restarted and a guard has no finish it could have reached.
 */
final readonly class Guards implements Guarding
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function guard(Stack $stack, Session $session, AGuardAskedFor $asked): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);
        $forms = [];

        foreach ($asked->forms() as $form) {
            $forms[] = $form->named();
        }

        try {
            $envelope = $client->act(
                new WatchAction(forms: $forms),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing
    {
        try {
            return $this->standing(GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown()));
        } catch (CertificateWasRefused|NoSuchJob|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|WatchIsUnreadable $why) {
            return HowTheGuardIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * Releasing a name and asking about it are two questions with one answer,
     * where the guard now stands, so the answer is read the same way.
     */
    public function letGo(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing
    {
        try {
            return $this->standing(GatedClient::of($this->clients, $stack, $session)->letGoOf($job->shown()));
        } catch (CertificateWasRefused|NoSuchJob|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|WatchIsUnreadable $why) {
            return HowTheGuardIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** Where the guard stands, from the standing asking or releasing it answered. */
    private function standing(JobStanding $standing): HowTheGuardIsGoing
    {
        return $standing->answering(
            stillRunning: static fn(): HowTheGuardIsGoing => HowTheGuardIsGoing::stillGuarding(),
            finished: static fn(Envelope $envelope): HowTheGuardIsGoing => HowTheGuardIsGoing::sawItGo(Vigils::in($envelope)),
            ended: static fn(): HowTheGuardIsGoing => HowTheGuardIsGoing::ended(),
        );
    }

    /**
     * What a refusal of the guard comes to.
     *
     * A name the stack does not know is a guard it no longer knows. A session
     * refused, an account that may not ask, or a machine that is not the one
     * paired is what the operator met; so is an answer carrying no sentence,
     * which is no refusal the stack made. Anything else is the stack's own
     * reason the guard did not start.
     */
    private function refusal(CertificateWasRefused|NoSuchJob|RequestFailed $why): HowTheGuardIsGoing
    {
        if ($why instanceof NoSuchJob) {
            return HowTheGuardIsGoing::unknown();
        }

        $met = WhatARefusalMeant::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;

        return $met->kind() !== KindOfObstacle::StackDidNotAnswer || $said === null
            ? HowTheGuardIsGoing::met($met)
            : HowTheGuardIsGoing::refused($said);
    }
}
