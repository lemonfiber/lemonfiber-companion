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
use Lemonfiber\Sdk\Generated\RestoreAction;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;
use Modules\Sdk\Internal\GatedClient;
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
 * Each asking carries a key of its own, the rehearsal included: a key on a
 * call that changes nothing is harmless, and one rule for every action leaves
 * none without one.
 *
 * **A refusal of the copy is the stack's answer, not a fault.** The rehearsal
 * is answered at once, so a copy the stack will not restore — one it cannot
 * read, one from a newer lemonfiber, one it does not manage — is refused
 * there. The yes is taken on as a job whatever it names, and the job is what
 * stops on a problem: a listing that moved on, a stack still running, a copy
 * that could not be unpacked. Both are carried in the stack's words, by
 * {@see WhatARefusalMeant::inItsWords()}; the yes itself is refused only in
 * prose, so it has nothing to carry.
 */
final readonly class Restorers implements PuttingBack
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function rehearse(Stack $stack, Session $session, ACopy $copy): WhatTheRestoreRehearsalFound
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                new RestoreAction(archive: $copy->name()),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return WhatTheRestoreRehearsalFound::listed(TheRestore::listedIn($envelope, $copy));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: WhatTheRestoreRehearsalFound::refused(...),
                met: WhatTheRestoreRehearsalFound::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|RestoreIsUnreadable|ScopeIsUnreadable|KeepingSaysNothing|ServiceIsUnnamed $why) {
            return WhatTheRestoreRehearsalFound::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function putBack(Stack $stack, Session $session, WhatPuttingItBackWouldDo $listed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                new RestoreAction(
                    archive: $listed->copy()->name(),
                    repoint: $listed->whereTheDataGoes()->isElsewhere(),
                    offer: $listed->agreement(),
                    confirm: true,
                ),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowPuttingItBackIsGoing::refused(...),
                met: HowPuttingItBackIsGoing::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|RestoreIsUnreadable|ScopeIsUnreadable|KeepingSaysNothing|ServiceIsUnnamed $why) {
            return HowPuttingItBackIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about putting it back, with only `NoSuchJob`
     * caught, for {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
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
