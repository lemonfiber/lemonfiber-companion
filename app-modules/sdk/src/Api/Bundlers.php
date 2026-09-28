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
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ABundleFetched;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\ABundleHasNoName;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\AWrittenBundle;
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
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Sdk\Api\Fields\BundleField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\Fields\WalkthroughField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Asking a stack for a support bundle, following it, and fetching it once written, through the SDK.
 *
 * Built as {@see Copiers} is: the client is fetched per stack and session, and
 * what the stack answers is read inside the same `try` as the request.
 *
 * **Fetching is a read, and the SDK's `bundle()` is the whole of it.** The file
 * comes back as the stack served it, for the operator to hand over through the
 * device's own sharing; nothing here sends it anywhere.
 *
 * **A refusal of the bundle is the stack's answer, not a fault.** Where the
 * work stopped on a problem — a credential redaction missed, a setting shown
 * without the yes — asking after it is answered with the refusal, and its
 * sentence is carried as {@see HowTheBundleIsGoing::refused()}. Only a refused
 * session, an account that may not ask, an answer with no sentence in it, and
 * a machine that is not the one paired are obstacles.
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
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName) {
            return Underway::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|BundleIsUnreadable|ABundleHasNoName) {
            return HowTheBundleIsGoing::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * The written bundle's file, as the stack serves it.
     *
     * A read and nothing else: the bytes come back whole and unopened, and the
     * type and length the transport stated are not carried, because the sheet
     * names the file by its name and hands over what arrived.
     */
    public function fetch(Stack $stack, Session $session, AWrittenBundle $written): ABundleFetched
    {
        try {
            $file = $this->clients->client($stack, $session)->bundle($written->name());

            return ABundleFetched::as(ABundleFile::fetched($written, $file->bytes()));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return ABundleFetched::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable) {
            return ABundleFetched::met(Obstacle::StackDidNotAnswer);
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
            WalkthroughField::Logs->value => $asked->lines()->figure(),
            BundleField::Filenames->value => $asked->filenames() === WhatFilenamesShow::Shown,
            BundleField::Reveal->value => $revealing,
            UpdateField::Confirm->value => $revealing !== [],
        ];
    }

    /**
     * What a refusal of the bundle comes to.
     *
     * A session refused, an account that may not ask, or a machine that is not
     * the one paired is what the operator met; so is an answer carrying no
     * sentence, which is no refusal the stack made. Anything else carries the
     * stack's own words, and what its problem document named in `detail`: the
     * source a credential was found in, which is what the operator acts on.
     */
    private function refusal(CertificateWasRefused|RequestFailed $why): HowTheBundleIsGoing
    {
        $met = WhatARefusalMeant::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;

        return $met !== Obstacle::StackDidNotAnswer || $said === null
            ? HowTheBundleIsGoing::met($met)
            : HowTheBundleIsGoing::refused($said, $this->named($why));
    }

    /** What the refusal's problem document named, or nothing where it was prose or named nothing. */
    private function named(RequestFailed $why): WhatTheRefusalNamed
    {
        $refusal = $why->refusal();
        $detail = $refusal instanceof Refusal ? $refusal->detail() : null;

        return is_string($detail) ? WhatTheRefusalNamed::as($detail) : WhatTheRefusalNamed::nothing();
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
