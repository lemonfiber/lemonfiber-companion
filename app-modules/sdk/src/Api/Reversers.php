<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_string;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Refusal;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Kernel\Api\WhyItWasNotPutBack;
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
 * that problem, and it is carried as {@see HowPuttingARunBackIsGoing::refused()},
 * as {@see Bundlers} carries a refused bundle. Only a refused session, an
 * account that may not ask, a machine that is not the one paired, and an
 * answer holding no problem document are obstacles: a sentence with no
 * document around it may have come from anything standing in front of the
 * stack, and is not taken as the stack's reason.
 */
final readonly class Reversers implements PuttingARunBack
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function putBack(Stack $stack, Session $session, ARunAgreedTo $agreed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoWithARun::PutBack->asked()),
                [WireField::At->value => $agreed->run()->stamp()],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|UndoIsUnreadable) {
            return HowPuttingARunBackIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What a refusal of the run comes to: the stack's problem where it sent
     * one with a sentence in it, and what was met everywhere else.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): HowPuttingARunBackIsGoing
    {
        $met = WhatARefusalMeant::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;
        $refusal = $why instanceof RequestFailed ? $why->refusal() : null;

        return $met !== Obstacle::StackDidNotAnswer || $said === null || ! $refusal instanceof Refusal
            ? HowPuttingARunBackIsGoing::met($met)
            : HowPuttingARunBackIsGoing::refused(WhyItWasNotPutBack::said($said, $refusal->meaning(), $this->named($refusal)));
    }

    /** What the problem named in `detail`, or nothing where it named nothing. */
    private function named(Refusal $refusal): WhatTheRefusalNamed
    {
        $detail = $refusal->detail();

        return is_string($detail) ? WhatTheRefusalNamed::as($detail) : WhatTheRefusalNamed::nothing();
    }

    /**
     * What the stack says about putting the run back, with only `NoSuchJob`
     * caught, for {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
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
