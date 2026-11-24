<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\ADecisionSaysNothing;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\AskingWhatIsLeftAlone;
use Modules\Kernel\Api\AskingWhatSetupDecided;
use Modules\Kernel\Api\HowItIsSet;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingIsUnnamed;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Modules\Kernel\Api\WhatWasFoundOfTheDecisions;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see Arranging}, answered by asking the stack.
 *
 * Holds nothing between calls. The listing is the stack's and is fetched every
 * time it is shown, because a cached one is exactly the silent subset the
 * requirement forbids: it would be right until the stack gained a setting and
 * then be quietly short, with nothing on the screen saying when it was taken.
 */
final readonly class Arrangements implements Arranging, AskingWhatIsLeftAlone, AskingWhatSetupDecided
{
    public function __construct(private Clients $clients) {}

    public function asItStands(Stack $stack, Session $session): HowItIsSet
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::CONFIG_ENDPOINT);

            // Inside the same `try` as the request, for {@see TheirOwn}'s
            // reason: a payload the client fetched and this side could not read
            // is the same thing to the operator as one that never arrived.
            return HowItIsSet::told(Dials::in($envelope), $this->leftAloneIn($envelope, $stack));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowItIsSet::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SettingIsUnreadable|SettingIsUnnamed $why) {
            return HowItIsSet::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function decidedOn(Stack $stack, Session $session): WhatWasFoundOfTheDecisions
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasFoundOfTheDecisions::found(Decisions::in($client->read(Api::CONFIG_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheDecisions::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SettingIsUnreadable|SettingIsUnnamed|ADecisionSaysNothing $why) {
            return WhatWasFoundOfTheDecisions::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function leftAloneOn(Stack $stack, Session $session): WhatWasFoundOfTheAreasLeftAlone
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return AreasLeftAlone::in($client->read(Api::CONFIG_ENDPOINT));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheAreasLeftAlone::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SettingIsUnreadable $why) {
            return WhatWasFoundOfTheAreasLeftAlone::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** @param Envelope<mixed> $envelope the `config` envelope, already read as its settings */
    private function leftAloneIn(Envelope $envelope, Stack $stack): WhatWasFoundOfTheAreasLeftAlone
    {
        try {
            return AreasLeftAlone::in($envelope);
        } catch (SettingIsUnreadable $why) {
            return WhatWasFoundOfTheAreasLeftAlone::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
