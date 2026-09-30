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
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemovalSaysNothing;
use Modules\Kernel\Api\RemovingSomebody;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack to take somebody out of the household.
 *
 * Built the way {@see Ushers} is: the client is fetched per stack and session,
 * the action is asked through {@see Api::action()} by the name
 * {@see TakingThemOut} spells, and the stack answers with a handle that
 * {@see self::whatBecameOf()} follows to the removal.
 *
 * **One action read twice.** Without the yes, `remove` says what taking them
 * out would cost and takes nobody out; with it, it takes them out. The yes
 * carries a key, because it changes the household; the reading does not.
 *
 * **A refusal keeps the stack's sentence**, for {@see Ushers}' reason: a name
 * it cannot find, or the account it signs in with, is turned down at a status
 * saying the fault is in the asking, and that sentence is the operator's
 * answer. **`NoSuchJob` is the stack having no outcome for the work.**
 */
final readonly class Removers implements RemovingSomebody
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function wouldRemove(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheRemoval
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(TakingThemOut::TakeThemOut->asked()),
                [WireField::Name->value => $who->name()],
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheRemoval::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function remove(Stack $stack, Session $session, ARemovalAgreed $agreed): WhatBecameOfTheRemoval
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(TakingThemOut::TakeThemOut->asked()),
                [
                    WireField::Name->value => $agreed->who()->name(),
                    UpdateField::Confirm->value => true,
                ],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheRemoval::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheRemoval
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|RemovalIsUnreadable|RemovalSaysNothing) {
            return WhatBecameOfTheRemoval::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught.
     *
     * Apart from {@see self::whatBecameOf()} for {@see Menders::standing()}'s
     * reason: a name the stack does not recognise is its answer that it has no
     * outcome, and belongs with the states rather than with the failures.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheRemoval
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheRemoval => WhatBecameOfTheRemoval::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheRemoval
                    => WhatBecameOfTheRemoval::answered(Removals::in($envelope)),
                ended: static fn(): WhatBecameOfTheRemoval => WhatBecameOfTheRemoval::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheRemoval::ended();
        }
    }

    /**
     * The handle an action was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheRemoval
    {
        try {
            return WhatBecameOfTheRemoval::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return WhatBecameOfTheRemoval::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, where
     * {@see WhatARefusalMeant::inItsOwnWords()} finds one, or an obstacle.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheRemoval
    {
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheRemoval::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheRemoval::refused($said);
    }
}
