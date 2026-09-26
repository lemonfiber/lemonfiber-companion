<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Sdk\Api\Fields\BundleField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack for a support bundle, and following it, through the SDK.
 *
 * Built as {@see Copiers} is: the client is fetched per stack and session, and
 * what the stack answers is read inside the same `try` as the request.
 *
 * **A refusal of the bundle is the stack's answer, not a fault.** Where the
 * work stopped on a problem — a credential redaction missed, a setting shown
 * without the yes — asking after it is answered with the refusal, and its
 * sentence is carried as {@see HowTheBundleIsGoing::refused()}. Only a refused
 * session, an account that may not ask, and an answer with no sentence in it
 * are obstacles.
 */
final readonly class Bundlers implements AskingForHelp
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function ask(Stack $stack, Session $session, ABundleAsked $asked): Underway
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action($asked->asked()),
                $this->arguments($asked),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (RequestFailed $why) {
            return $this->refusal($why);
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|BundleIsUnreadable) {
            return HowTheBundleIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What the action is sent: every choice, said every time.
     *
     * The yes goes with the settings it agrees to and only then. The stack
     * holds a bundle revealing a setting back until it is confirmed, and one
     * revealing nothing needs no yes, so sending one there would be agreeing
     * to nothing.
     *
     * @return array<string, bool|int|list<string>>
     */
    private function arguments(ABundleAsked $asked): array
    {
        $revealing = [];

        foreach ($asked->revealing() as $setting) {
            $revealing[] = $setting->name();
        }

        return [
            BundleField::Write->value => $asked->writes(),
            BundleField::Logs->value => $asked->lines()->figure(),
            BundleField::Filenames->value => $asked->filenames() === WhatFilenamesShow::Shown,
            BundleField::Reveal->value => $revealing,
            UpdateField::Confirm->value => $revealing !== [],
        ];
    }

    /**
     * What a refusal of the bundle comes to.
     *
     * A session refused, or an account that may not ask, is what the operator
     * met; so is an answer carrying no sentence, which is no refusal the stack
     * made. Anything else carries the stack's own words.
     */
    private function refusal(RequestFailed $why): HowTheBundleIsGoing
    {
        $met = WhatARefusalMeant::obstacle($why);
        $said = $why->said();

        return $met !== Obstacle::StackDidNotAnswer || $said === null
            ? HowTheBundleIsGoing::met($met)
            : HowTheBundleIsGoing::refused($said);
    }

    /**
     * What the stack says about the bundle, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowTheBundleIsGoing => HowTheBundleIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowTheBundleIsGoing
                    => HowTheBundleIsGoing::done(TheBundle::in($envelope)),
                ended: static fn(): HowTheBundleIsGoing => HowTheBundleIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowTheBundleIsGoing::ended();
        }
    }
}
