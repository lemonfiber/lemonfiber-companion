<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack what putting a copy back would do, telling it to, and
 * following it, through the SDK.
 *
 * **One action read twice.** Without the yes, `restore` reads the copy's own
 * account of itself and changes nothing, and the stack answers at once. With
 * the yes and the listing's name, it stops what it must, puts the copy back
 * and answers with a handle to follow. A yes naming a listing the stack no
 * longer offers is refused by the stack rather than carried out.
 *
 * **The data is re-pointed only where the listing said it would be.** A copy
 * taken against another data root lands its data on this machine's, and the
 * listing says so before anything is agreed; agreeing to that listing is
 * agreeing to that, so the yes carries it and a listing that moved nothing
 * carries nothing of the kind.
 *
 * The rehearsal carries no key, because it changes nothing; the yes carries
 * one, because it does.
 */
final readonly class Restorers implements PuttingBack
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function rehearse(Stack $stack, Session $session, ACopy $copy): WhatTheRestoreRehearsalFound
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoWithACopy::PutBack->asked()),
                [RestoreField::Archive->value => $copy->name()],
            );

            return WhatTheRestoreRehearsalFound::listed(TheRestore::listedIn($envelope, $copy));
        } catch (RequestFailed $why) {
            return WhatTheRestoreRehearsalFound::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|RestoreIsUnreadable|ScopeIsUnreadable|KeepingSaysNothing|ServiceIsUnnamed) {
            return WhatTheRestoreRehearsalFound::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function putBack(Stack $stack, Session $session, WhatPuttingItBackWouldDo $listed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoWithACopy::PutBack->asked()),
                [
                    RestoreField::Archive->value => $listed->copy()->name(),
                    UpdateField::Confirm->value => true,
                    RestoreField::Offer->value => $listed->agreement(),
                    RestoreField::Repoint->value => $listed->whereTheDataGoes()->isElsewhere(),
                ],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (RequestFailed $why) {
            return HowPuttingItBackIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|RestoreIsUnreadable|ScopeIsUnreadable|KeepingSaysNothing|ServiceIsUnnamed) {
            return HowPuttingItBackIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about putting it back, with only `NoSuchJob`
     * caught, for {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowPuttingItBackIsGoing => HowPuttingItBackIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowPuttingItBackIsGoing
                    => HowPuttingItBackIsGoing::done(TheRestore::doneIn($envelope)),
                ended: static fn(): HowPuttingItBackIsGoing => HowPuttingItBackIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowPuttingItBackIsGoing::ended();
        }
    }
}
