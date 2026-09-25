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
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Sdk\Internal\WhatADecisionAsksWith;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack to walk through fetching one thing.
 *
 * {@see Upkeepers} one action over, and built the same way: the action answers
 * a handle, and the handle is asked after until it answers the walkthrough's
 * own report.
 */
final readonly class Guides implements WalkingThrough
{
    public function __construct(private Clients $clients) {}

    public function walk(Stack $stack, Session $session, WhatToWalk $asked): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            // The item where one was named and nothing where none was: the
            // stack reads a missing item as *suggest something likely to
            // work*, and says what it chose in the report.
            $envelope = $client->act(Api::action($asked->asked()), $asked->either(
                named: static fn(string $item): WhatADecisionAsksWith => new WhatADecisionAsksWith([WireField::Item->value => $item]),
                likeliest: static fn(): WhatADecisionAsksWith => new WhatADecisionAsksWith([]),
            )->said);

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheWalkthroughIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (RequestFailed $why) {
            return HowTheWalkthroughIsGoing::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|WalkthroughIsUnreadable) {
            return HowTheWalkthroughIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the stack says about the job, with only `NoSuchJob` caught.
     *
     * A name lemonfiber does not recognise is the stack answering that it has
     * no outcome for that walkthrough, which is one of the states the reading
     * returns rather than a failure to reach the machine; {@see Upkeepers}
     * draws the same line for the same reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheWalkthroughIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheWalkthroughIsGoing => HowTheWalkthroughIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheWalkthroughIsGoing
                    => HowTheWalkthroughIsGoing::done(Walkthroughs::in($envelope)),
                ended: static fn(): HowTheWalkthroughIsGoing => HowTheWalkthroughIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheWalkthroughIsGoing::ended();
        }
    }
}
