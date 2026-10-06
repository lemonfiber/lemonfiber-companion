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
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Telling a stack to put back one run of changes, and following it, through the SDK.
 *
 * Built as {@see Restorers} is. `undo` takes one argument, the stamp the record
 * keeps the run under, and no yes: the agreement is made on this side against
 * the record's own rows, and a stamp naming no run, or more than one, is
 * refused by the stack rather than guessed at here.
 *
 * **Asking carries a key, because it changes a stack.** Following asks after
 * work already named and changes nothing, so it carries none.
 *
 * **A refusal of the run is the stack's answer, not a fault.** The action is
 * taken on as a job whatever stamp it names, and the job is what stops on a
 * problem: no run under that stamp, more than one, a change that cannot be
 * reversed, or one that could not be. Asking after it is then answered with
 * that problem, and {@see WhatARefusalMeant::inItsWords()} carries it as
 * {@see HowPuttingARunBackIsGoing::refused()}, as it does a copy the stack will
 * not restore.
 */
final readonly class Reversers implements PuttingARunBack
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function putBack(Stack $stack, Session $session, ARunAgreedTo $agreed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoWithARun::PutBack->asked()),
                [WireField::At->value => $agreed->run()->stamp()],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowPuttingARunBackIsGoing::refused(...),
                met: HowPuttingARunBackIsGoing::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|UndoIsUnreadable $why) {
            return HowPuttingARunBackIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about putting the run back, with only `NoSuchJob`
     * caught, for {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowPuttingARunBackIsGoing => HowPuttingARunBackIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowPuttingARunBackIsGoing
                    => HowPuttingARunBackIsGoing::done(TheRunPutBack::in($envelope)),
                ended: static fn(): HowPuttingARunBackIsGoing => HowPuttingARunBackIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowPuttingARunBackIsGoing::ended();
        }
    }
}
