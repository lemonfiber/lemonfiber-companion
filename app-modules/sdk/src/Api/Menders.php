<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Repair as Asking;
use Modules\Kernel\Api\HowTheOfferIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it would put right.
 *
 * `N1-R16` says every call goes through the SDK, so this sits beside
 * {@see Questions} and {@see Requests} and is built the same way: it asks
 * {@see PinnedClients} for the connection rather than naming a client
 * constructor, which is what keeps the certificate pin in a single file.
 *
 * **`Repair::offer()` is the unconfirmed form**, and the SDK makes the two
 * refused consent arrangements unrepresentable rather than refusing them at
 * runtime. That is why this class can ask *what would you do* without any
 * possibility of carrying something out: there is no argument here that could
 * turn the question into an instruction.
 *
 * **`NoSuchJob` is the third state, not an error.** The SDK raises it for a
 * name lemonfiber does not recognise, which is a job that was let go of, or one
 * belonging to a run that has since restarted. Reading it as a fault would send
 * an operator to look at a machine that is working; reading it as still-running
 * would spin on a handle nothing will ever answer for. It becomes
 * {@see HowTheOfferIsGoing::ended()}, whose remedy is to ask again.
 *
 * **A {@see \Modules\Kernel\Api\JobHasNoName} is not caught**, and the asymmetry is deliberate. It means
 * a stack acknowledged an action and named it with nothing — the one state
 * `N1-R41` has no answer for, since the action *was* delivered and so must not
 * be sent again, and there is no handle to ask after it by. Swallowing it into
 * an obstacle would present *the machine is not answering* for a machine that
 * answered, and would lose the only evidence that the exchange is broken.
 */
final readonly class Menders implements Mending
{
    public function __construct(private PinnedClients $clients) {}

    public function wouldPutRight(Stack $stack, Session $session): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            return Underway::as(Offers::handleIn($client->repair(Asking::offer())));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|OfferIsUnreadable) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing
    {
        try {
            return $this->standing($stack, $session, $job);
        } catch (RequestFailed $why) {
            return HowTheOfferIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|OfferIsUnreadable) {
            return HowTheOfferIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about that job, with nothing caught.
     *
     * Split from {@see whatBecameOf()} because the two are different questions
     * — what the stack said, and what to make of not being able to ask it — and
     * because `H8` counts the doors one method would otherwise have.
     *
     * `NoSuchJob` is caught *here* rather than beside the other raises, and the
     * placement is the argument: a name lemonfiber does not recognise is the
     * stack answering, clearly, that it has no outcome for that job. It belongs
     * with the three states the reading returns and not with the two ways of
     * failing to reach a machine — which is exactly the fold
     * {@see HowTheOfferIsGoing} exists to refuse.
     */
    private function standing(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing
    {
        try {
            return $this->reading($stack, $session, $job);
        } catch (NoSuchJob) {
            return HowTheOfferIsGoing::ended();
        }
    }

    /**
     * What the stack said about that job, with nothing caught at all.
     *
     * The half with no error handling in it, which is what makes the three
     * states it returns easy to check against the port one by one.
     */
    private function reading(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing
    {
        return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
            stillRunning: static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::stillRunning(),
            finished: static fn(Envelope $envelope): HowTheOfferIsGoing
                => HowTheOfferIsGoing::offering(Offers::offerIn($envelope)),
            ended: static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::ended(),
        );
    }
}
