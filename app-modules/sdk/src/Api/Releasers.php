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
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\HowTheOfferToLetGoIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhatToDoWithADownload;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Api\Fields\StopSeedingField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack what stopping seeding one download would cost, telling it to,
 * and following each, through the SDK.
 *
 * **One action read twice, each answered as a job.** Naming only the download,
 * `stop-seeding` reads what letting it go would cost and changes nothing.
 * Naming the offer as well, it asks the download client to let it go, files and
 * all. There is no `confirm` on this action: the offer's own name is the yes,
 * so the only road to the removal runs through a reading that stated the cost,
 * and a yes naming an offer that has moved since is refused by the stack.
 *
 * The question carries no key, because it changes nothing; the yes carries
 * one, because it does. `offer` is the word a restore's yes quotes its listing
 * by, and this action quotes its offer by the same word.
 */
final readonly class Releasers implements StoppingSeeding
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function whatItWouldCost(Stack $stack, Session $session, ADownloadHeld $download): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            return Underway::as(Handles::in($client->act(
                Api::action(WhatToDoWithADownload::StopSeeding->asked()),
                [StopSeedingField::Download->value => $download->name()],
            )));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatTheOfferCameTo(Stack $stack, Session $session, Job $job): HowTheOfferToLetGoIsGoing
    {
        try {
            return $this->offer($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowTheOfferToLetGoIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|StopSeedingIsUnreadable|RoomSaysNothing) {
            return HowTheOfferToLetGoIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function stop(Stack $stack, Session $session, WhatLettingItGoCosts $offer): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            return Underway::as(Handles::in($client->act(
                Api::action(WhatToDoWithADownload::StopSeeding->asked()),
                [
                    StopSeedingField::Download->value => $offer->download()->name(),
                    RestoreField::Offer->value => $offer->agreement(),
                ],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            )));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowLettingItGoIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowLettingItGoIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|StopSeedingIsUnreadable|RoomSaysNothing) {
            return HowLettingItGoIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about asking what it would cost, with only `NoSuchJob`
     * caught, for {@see Menders::standing()}'s reason.
     */
    private function offer(Stack $stack, Session $session, Job $job): HowTheOfferToLetGoIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheOfferToLetGoIsGoing => HowTheOfferToLetGoIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheOfferToLetGoIsGoing
                    => HowTheOfferToLetGoIsGoing::offering(WhatLettingItGoComesTo::offerIn($envelope)),
                ended: static fn(): HowTheOfferToLetGoIsGoing => HowTheOfferToLetGoIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheOfferToLetGoIsGoing::ended();
        }
    }

    /**
     * What the stack says about letting it go, with only `NoSuchJob` caught,
     * for {@see Menders::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowLettingItGoIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowLettingItGoIsGoing => HowLettingItGoIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowLettingItGoIsGoing
                    => HowLettingItGoIsGoing::done(WhatLettingItGoComesTo::goneIn($envelope)),
                ended: static fn(): HowLettingItGoIsGoing => HowLettingItGoIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowLettingItGoIsGoing::ended();
        }
    }
}
