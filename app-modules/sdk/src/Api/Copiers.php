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
use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingCopies;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Telling a stack to take a copy of itself, and following it, through the SDK.
 *
 * Built as {@see Upkeepers} is: the client is fetched per stack and session,
 * and what the stack answers is read inside the same `try` as the request. A
 * field a kernel type refuses — a blank name, a size below nothing — is an
 * answer this app cannot read, and is the same obstacle as one that never
 * arrived.
 *
 * **Asking carries a key, because it changes a stack.** Following asks after
 * work already named and changes nothing, so it carries none.
 */
final readonly class Copiers implements TakingCopies
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function take(Stack $stack, Session $session, ACopyAsked $asked): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoWithACopy::Take->asked()),
                $this->narrowing($asked),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheCopyIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowTheCopyIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|BackupIsUnreadable|ScopeIsUnreadable|KeepingSaysNothing|ServiceIsUnnamed) {
            return HowTheCopyIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * The one argument asking takes: the service a copy is narrowed to, and
     * nothing for the whole stack.
     *
     * @return array<string, string>
     */
    private function narrowing(ACopyAsked $asked): array
    {
        $named = '';

        foreach ($asked->narrowedTo() as $service) {
            $named = $service->named();
        }

        return $named === '' ? [] : [WireField::Service->value => $named];
    }

    /**
     * What the stack says about the copy, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheCopyIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheCopyIsGoing => HowTheCopyIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheCopyIsGoing
                    => HowTheCopyIsGoing::done(TheCopyTaken::in($envelope)),
                ended: static fn(): HowTheCopyIsGoing => HowTheCopyIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheCopyIsGoing::ended();
        }
    }
}
