<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\ActionRequest;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\DownloadsPauseAction;
use Lemonfiber\Sdk\Generated\DownloadsResumeAction;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowPausingIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\PausingDownloads;
use Modules\Kernel\Api\PausingSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Rehearsing a pause or a resume of every download client, carrying it out, and following each, through the SDK.
 *
 * **One action each way, read twice, each answered as a job.** Asked with
 * `dry_run`, `downloads-pause` and `downloads-resume` ask no client anything
 * and answer what each is doing, under an offer. Carrying that offer back,
 * they ask every client, and are refused where any has moved since, so what
 * is paused is what was shown.
 *
 * Both carry a key: the rehearsal changes nothing, and a key still names this
 * asking apart from the yes, as {@see Supervisors::rehearsed()} does.
 */
final readonly class Pausers implements PausingDownloads
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function rehearse(Stack $stack, Session $session, WhatToDoWithTheDownloads $asked): Underway
    {
        return $this->underway($stack, $session, $this->action($asked)->rehearsed());
    }

    public function carryOut(Stack $stack, Session $session, WhatPausingComesTo $offer): Underway
    {
        return $this->underway($stack, $session, $this->action($offer->asked(), $offer->agreement()));
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPausingIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowPausingIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PausingIsUnreadable|PausingSaysNothing $why) {
            return HowPausingIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** The action for that direction, carrying the offer where one is agreed to. */
    private function action(WhatToDoWithTheDownloads $asked, ?string $offer = null): DownloadsPauseAction|DownloadsResumeAction
    {
        return match ($asked) {
            WhatToDoWithTheDownloads::Pause => new DownloadsPauseAction(offer: $offer),
            WhatToDoWithTheDownloads::Resume => new DownloadsResumeAction(offer: $offer),
        };
    }

    private function underway(Stack $stack, Session $session, ActionRequest $action): Underway
    {
        try {
            return Underway::as(Handles::in(GatedClient::of($this->clients, $stack, $session)->act(
                $action,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            )));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the job, with only `NoSuchJob` caught, for
     * {@see Menders::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowPausingIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowPausingIsGoing => HowPausingIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowPausingIsGoing => HowPausingIsGoing::answered(WhatPausingCameTo::in($envelope)),
                ended: static fn(): HowPausingIsGoing => HowPausingIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowPausingIsGoing::ended();
        }
    }
}
