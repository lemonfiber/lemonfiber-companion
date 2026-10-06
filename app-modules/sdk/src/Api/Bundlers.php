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
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Sdk\Api\Fields\BundleField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\Fields\WalkthroughField;
use Modules\Sdk\Internal\GatedClient;
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
 * **A refusal of the bundle is the stack's answer, not a fault.** The stack
 * takes `support` on as work whatever it is asked, and refuses a bundle only
 * by stopping that work on a problem — a setting shown without the yes, a
 * credential still in what was gathered, nowhere to write the archive — which
 * asking after it answers with the problem document. So a refused bundle
 * follows the one rule every refusal in the stack's words follows,
 * {@see WhatARefusalMeant::inItsWords()}, and is carried as
 * {@see HowTheBundleIsGoing::refused()}.
 */
final readonly class Bundlers implements AskingForHelp
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function ask(Stack $stack, Session $session, ABundleAsked $asked): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                Api::action($asked->asked()),
                $this->arguments($asked),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowTheBundleIsGoing::refused(...),
                met: HowTheBundleIsGoing::met(...),
            );
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|BundleIsUnreadable|ABundleHasNoName $why) {
            return HowTheBundleIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
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
            $file = GatedClient::of($this->clients, $stack, $session)->bundle($written->name());

            return ABundleFetched::as(ABundleFile::fetched($written, $file->bytes()));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return ABundleFetched::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable $why) {
            return ABundleFetched::met($this->clients->whatStoodInTheWay($stack, $why));
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
     * What the stack says about the bundle, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
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
