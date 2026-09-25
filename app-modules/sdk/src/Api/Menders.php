<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Repair as Asking;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\EffectSaysNothing;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheOfferIsGoing;
use Modules\Kernel\Api\HowTheRepairIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OfferHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it would put right.
 *
 * Every call to a stack goes through the SDK, so this sits beside
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
 * **The yes carries a key; the question does not.** An idempotency key goes on every
 * action that changes a stack, and {@see self::agreeTo()} is this class's only
 * one. {@see self::wouldPutRight()} travels the same door and changes nothing:
 * `Repair::offer()` asks what *would* be done and carries out none of it, so a
 * key there would name an attempt at nothing and invite a stack to one day
 * answer a fresh question with an old answer. The line is the requirement's own
 * — *every action that changes a stack* — rather than every request that
 * happens to be a `POST`.
 *
 * **A {@see JobHasNoName} is an obstacle**, as every answer this class cannot
 * read is. It means a stack acknowledged an action and named it with nothing,
 * which leaves no handle to ask after it by. The screen draws that as the
 * stack not answering, where an exception would draw it as a crash.
 */
final readonly class Menders implements Mending
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function wouldPutRight(Stack $stack, Session $session): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            return Underway::as(Handles::in($client->repair(Asking::offer())));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|OfferIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function agreeTo(Stack $stack, Session $session, Confirmed $confirmed): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            // The listing's name and the repair's check, which is exactly what
            // `Confirmed` publishes and nothing else. A yes must quote
            // the listing it was given, and the SDK's signature is that
            // requirement in a parameter list: there is no way to name a repair
            // without naming the listing it came from.
            $asked = Asking::agreedTo($confirmed->quoting(), $confirmed->repair()->answers()->shown());

            // The key is built inline, as {@see Supervisors::told()} builds
            // its own. A name for this attempt that outlived the statement
            // making it is a name a later attempt could be sent under.
            return Underway::as(Handles::in(
                $client->repair($asked, IdempotencyKey::from($this->entropy->nonce())->sent()),
            ));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|OfferIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatWasDoneAbout(Stack $stack, Session $session, Job $job): HowTheRepairIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (RequestFailed $why) {
            return HowTheRepairIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|OfferIsUnreadable|EffectSaysNothing) {
            return HowTheRepairIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing
    {
        try {
            return $this->standing($stack, $session, $job);
        } catch (RequestFailed $why) {
            return HowTheOfferIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|OfferIsUnreadable|OfferHasNoName|EffectSaysNothing) {
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
     * What the stack says about a job that was carrying something out.
     *
     * `NoSuchJob` is caught here for {@see self::standing()}'s reason, and it
     * matters more on this side: a job that ended after an agreement means the
     * operator does not know what happened to their machine, which is a thing
     * to say rather than a failure to report.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheRepairIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheRepairIsGoing
                    => HowTheRepairIsGoing::done(Offers::mendedIn($envelope)),
                ended: static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheRepairIsGoing::ended();
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
