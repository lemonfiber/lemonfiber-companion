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
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingItOff;
use Modules\Kernel\Api\TakingLemonfiberOff;
use Modules\Kernel\Api\UninstallSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhatWasFoundOfTheUninstall;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Api\Fields\UninstallField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what taking lemonfiber off would come to, and to do it.
 *
 * **The reading is a read.** `/api/uninstall` answers at once with what one
 * removal would reach, removing nothing, for the tier it is asked about.
 *
 * **The removal is the action, and it quotes the reading.** `uninstall` is
 * asked with the tier, the yes, the reading's name and whether to let what
 * is still coming down land, and the stack answers with a handle
 * {@see self::whatBecameOf()} follows. The yes carries a key, because it
 * changes the machine.
 *
 * **A refusal keeps the stack's sentence**, for {@see Ushers}' reason: an
 * agreement missing where the library is taken, or naming a reading that no
 * longer stands, is turned down with a sentence that is the operator's answer.
 */
final readonly class Dismantlers implements TakingLemonfiberOff
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function surveyed(Stack $stack, Session $session, WhichRemoval $tier): WhatWasFoundOfTheUninstall
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::UNINSTALL_ENDPOINT, [UninstallField::Tier->value => $tier->value]);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheUninstall::found(Uninstalls::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheUninstall::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|UninstallIsUnreadable|UninstallSaysNothing|RoomSaysNothing) {
            return WhatWasFoundOfTheUninstall::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function takeItOff(Stack $stack, Session $session, AnUninstallAgreed $agreed): WhatBecameOfTheUninstall
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(TakingItOff::TakeItOff->asked()),
                [
                    UninstallField::Tier->value => $agreed->tier()->value,
                    UpdateField::Confirm->value => true,
                    RestoreField::Offer->value => $agreed->agreement(),
                    UninstallField::Wait->value => $agreed->waiting()->waits(),
                ],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse) {
            return WhatBecameOfTheUninstall::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheUninstall
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|UninstallIsUnreadable|UninstallSaysNothing|RoomSaysNothing) {
            return WhatBecameOfTheUninstall::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught.
     *
     * Apart from {@see self::whatBecameOf()} for {@see Menders::standing()}'s
     * reason: a name the stack does not recognise is its answer that it has no
     * outcome, and belongs with the states rather than with the failures.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheUninstall
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheUninstall => WhatBecameOfTheUninstall::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheUninstall
                    => WhatBecameOfTheUninstall::answered(Uninstalls::in($envelope)),
                ended: static fn(): WhatBecameOfTheUninstall => WhatBecameOfTheUninstall::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheUninstall::ended();
        }
    }

    /**
     * The handle the action was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheUninstall
    {
        try {
            return WhatBecameOfTheUninstall::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return WhatBecameOfTheUninstall::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What a request the stack turned down means: its own sentence, where
     * {@see WhatARefusalMeant::inItsOwnWords()} finds one, or an obstacle.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheUninstall
    {
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheUninstall::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheUninstall::refused($said);
    }
}
