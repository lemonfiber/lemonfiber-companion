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
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\ARevertCannotBeShown;
use Modules\Kernel\Api\AStackEditCannotBeShown;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatToChange;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack what putting its configuration back would revert, telling it
 * to, and following either, through the SDK.
 *
 * **One action asked twice.** Without the yes, `reset` compares the operator's
 * files and connections with lemonfiber's and writes nothing; with it, it
 * writes lemonfiber's back. The stack answers both with a handle, and both
 * finish as the `reset` envelope, told apart by its `confirmed`.
 *
 * **The yes quotes nothing.** The action takes `confirm` and no other
 * argument, so the stack reverts every edit it finds when the yes arrives.
 * What it reverted is what its report says, and the report is what the screen
 * draws after the yes.
 *
 * The preview carries no key, because it changes nothing; the yes carries one,
 * because it does.
 *
 * **A refusal of the reset is the stack's answer, not a fault.** Both asks
 * are taken on as work, and the work is what stops on a problem — a recorded
 * choice it could not read, a file it could not write — so a refused reset
 * follows {@see WhatARefusalMeant::inItsWords()} and is carried as
 * {@see HowTheResetIsGoing::refused()}, as a refused bundle is.
 */
final readonly class Resetters implements ResettingTheConfiguration
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function wouldRevert(Stack $stack, Session $session): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return Underway::as(Handles::in($client->act(
                Api::action(WhatToChange::BackToItsOwn->asked()),
                [UpdateField::Confirm->value => false],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            )));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function revert(Stack $stack, Session $session, AResetAgreed $agreed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return Underway::as(Handles::in($client->act(
                Api::action(WhatToChange::BackToItsOwn->asked()),
                [UpdateField::Confirm->value => true],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            )));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheResetIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowTheResetIsGoing::refused(...),
                met: HowTheResetIsGoing::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|ResetIsUnreadable|ARevertCannotBeShown|StackEditsAreUnreadable|AStackEditCannotBeShown $why) {
            return HowTheResetIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }


    /**
     * What the stack says about the reset, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheResetIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheResetIsGoing => HowTheResetIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheResetIsGoing
                    => HowTheResetIsGoing::done(WhatAResetReverts::in($envelope)),
                ended: static fn(): HowTheResetIsGoing => HowTheResetIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheResetIsGoing::ended();
        }
    }
}
