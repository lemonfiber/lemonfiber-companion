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
use Lemonfiber\Sdk\Generated\DownAction;
use Lemonfiber\Sdk\Generated\PullAction;
use Lemonfiber\Sdk\Generated\RefusalCode;
use Lemonfiber\Sdk\Generated\RestartAction;
use Lemonfiber\Sdk\Generated\UpAction;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AStackEditCannotBeShown;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatFormsThereAre;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\Quoted;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what it is running, and tells it
 * to change that.
 *
 * Every call to a stack goes through the SDK, so this sits beside
 * {@see Stalls} and {@see Menders} and is written the same way: it asks
 * {@see PinnedClients} for the connection rather than building one, which is
 * what keeps the certificate pin in a single file. This class never names a
 * client constructor, so it cannot make a decision about whether a certificate
 * is checked.
 *
 * **The verb reaches the wire as the SDK's own action.** Each verb is a class
 * the SDK generates from the contract, so no caller spells its name, its path
 * or its arguments, and an argument the action does not take is a mistake the
 * analyser points at.
 *
 * **A form and a service are different arguments, not one narrowing.** Stopping
 * a whole form is `forms`, stopping one of its services is `services`, and the
 * surface reads them as two requests rather than one with an option — which is
 * why {@see AgreedTo} carries one or the other and this asks it which.
 *
 * **Four raises, two answers**, which is {@see Stalls}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone. The exception is a refused session, which is a
 * different sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Stalls}'
 * reason: it means this app is holding a stack it should never have written
 * down, and catching it would turn a fault in retained state into an ordinary
 * screen about an unreachable machine.
 *
 * **The key that names an attempt is minted here and nowhere else.** An idempotency key
 * wants one on every action that changes a stack, and wants it to serve the
 * retry inside a single attempt rather than a replay across a reconnection.
 * Those are the same sentence read twice: a key is only safe while it names
 * the attempt it was made for, so this asks {@see Entropy} for a fresh one at
 * the moment of sending and keeps nothing. {@see Supervising} deliberately
 * does not take one — a port that accepted a key is a port a caller can hand
 * the same key to twice, which is the replay an action held for later
 * would be, arriving through the one door built to prevent it.
 *
 * **Following a verb carries no key**, for {@see Copiers}' reason: it asks
 * after work already named and changes nothing. What the verb came to is read
 * by {@see Lifecycles} inside the same `try` as the request, so a report this
 * app cannot read is the same obstacle as one that never arrived.
 */
final readonly class Supervisors implements Supervising
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function running(Stack $stack, Session $session): WhatIsRunning
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::STATUS_ENDPOINT);

            return WhatIsRunning::these(Rosters::in($envelope), Rosters::whatElseIsRunning($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatIsRunning::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|RosterIsUnreadable $why) {
            return WhatIsRunning::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * Every form the stack declares, off the one answer that lists them.
     *
     * The status envelope's `forms` are the forms that reading asked about —
     * none, for the whole stack — and a service's profile is not a form, so
     * the forms a verb can be asked for by come only from their own endpoint.
     */
    public function formsOn(Stack $stack, Session $session): WhatFormsThereAre
    {
        try {
            return WhatFormsThereAre::these(Repertoires::in(GatedClient::of($this->clients, $stack, $session)->read(Api::FORMS_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatFormsThereAre::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|RepertoireIsUnreadable $why) {
            return WhatFormsThereAre::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                $this->action($agreed),
                // Built inline rather than into a variable. A key held for the
                // length of a method is a key a second statement can reach, and
                // this method's whole obligation is that no second send ever
                // sees the first one's name.
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function rehearsed(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            // Under a key of its own, as every action is: a rehearsal changes
            // nothing, and a key still names this asking apart from the yes.
            $envelope = $client->act(
                $this->action($agreed)->rehearsed(),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return Underway::as(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return Underway::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return Underway::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** @return HowAgreedWorkIsGoing<WhatTheVerbCameTo> */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowAgreedWorkIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::whereItMoved(RefusalCode::RestartMoved, $why, HowAgreedWorkIsGoing::moved(...), HowAgreedWorkIsGoing::met(...));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|LifecycleIsUnreadable|StackEditsAreUnreadable|AStackEditCannotBeShown $why) {
            return HowAgreedWorkIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the verb, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     * @return HowAgreedWorkIsGoing<WhatTheVerbCameTo>
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowAgreedWorkIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowAgreedWorkIsGoing => HowAgreedWorkIsGoing::stillRunning(),
                finished: static fn(Envelope $envelope): HowAgreedWorkIsGoing
                    => HowAgreedWorkIsGoing::done(Lifecycles::in($envelope)),
                ended: static fn(): HowAgreedWorkIsGoing => HowAgreedWorkIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowAgreedWorkIsGoing::ended();
        }
    }

    /**
     * The verb the operator agreed to, about the form or the service it names.
     *
     * A form travels under `forms` and a service under `services`: a form's
     * name sent as a service's would stop nothing and report that it had. A
     * fetch is only ever about a form, which {@see AgreedTo} holds to.
     */
    private function action(AgreedTo $agreed): UpAction|DownAction|RestartAction|PullAction
    {
        $forms = $agreed->isAboutAForm() ? [$agreed->named()] : [];
        $services = $agreed->isAboutAForm() ? [] : [$agreed->named()];

        return match ($agreed->doing()) {
            WhatToDoWithIt::Start => new UpAction(forms: $forms, services: $services),
            WhatToDoWithIt::Stop => new DownAction(forms: $forms, services: $services),
            WhatToDoWithIt::Restart => new RestartAction(forms: $forms, services: $services, offer: Quoted::offer($agreed->offer())),
            WhatToDoWithIt::Pull => new PullAction(forms: $forms),
        };
    }
}
