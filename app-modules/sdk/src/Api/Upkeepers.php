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
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\Generated\UpdateAction;
use Modules\Kernel\Api\AStackEditCannotBeShown;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheUpdateIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatIsCurrent;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\Quoted;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhichUpdate;

/**
 * The stack's own upkeep, asked through the SDK.
 *
 * {@see Supervisors} one conversation over, and built the same way: the client
 * is fetched per stack and session so that separate sessions and separate
 * pinning cannot be paired up wrongly.
 */
final readonly class Upkeepers implements KeepingCurrent
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function standing(Stack $stack, Session $session): WhatIsCurrent
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            // Naming what this is about, because the endpoint serves two things
            // and a request that says neither is answered in prose rather than
            // with an envelope. This asks about the services; where the running
            // copy of lemonfiber stands is {@see Inspectors}' reading.
            $envelope = $client->read(
                Api::UPDATE_ENDPOINT,
                [WireField::What->value => WhichUpdate::TheStack->value],
            );

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched and
            // this side could not read is the same thing to an operator as one
            // that never arrived.
            return WhatIsCurrent::stands(Standings::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatIsCurrent::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|UpkeepIsUnreadable|ChangelogIsUnreadable|ServiceIsUnnamed|StackEditsAreUnreadable|AStackEditCannotBeShown $why) {
            return WhatIsCurrent::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function take(Stack $stack, Session $session, TakingAnUpdate $agreed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            // `confirm`, and the offer the reading named. Unconfirmed, the
            // stack's `update` action only says what would change; confirmed,
            // it moves every service it listed and did not refuse, which is
            // the list `TakingAnUpdate::changing()` holds and the confirmation
            // named. The offer is carried back so the stack refuses the yes
            // where what it would apply has moved since. The action narrows
            // to one `service` and takes no list, so none is named: one would
            // narrow the run.
            $envelope = $client->act(
                new UpdateAction(offer: Quoted::offer($agreed->offer()), confirm: true),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheUpdateIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::whereItMoved(RefusalCode::UpdateMoved, $why, HowTheUpdateIsGoing::moved(...), HowTheUpdateIsGoing::met(...));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|UpkeepIsUnreadable|ChangelogIsUnreadable|ServiceIsUnnamed|StackEditsAreUnreadable|AStackEditCannotBeShown $why) {
            return HowTheUpdateIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the job, with only `NoSuchJob` caught.
     *
     * A name lemonfiber does not recognise is the stack answering that it has
     * no outcome for that update, which is one of the states the reading
     * returns rather than a failure to reach the machine; {@see Menders} draws
     * the same line for the same reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheUpdateIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheUpdateIsGoing => HowTheUpdateIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheUpdateIsGoing
                    => HowTheUpdateIsGoing::done(Standings::in($envelope)),
                ended: static fn(): HowTheUpdateIsGoing => HowTheUpdateIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheUpdateIsGoing::ended();
        }
    }
}
