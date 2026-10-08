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
use Lemonfiber\Sdk\Generated\MusicEnvelope;
use Lemonfiber\Sdk\Generated\QualitySetAction;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhatWasFoundOfTheQuality;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see ChoosingQuality}, answered by asking the stack.
 *
 * **One action, two calls, and the difference is `confirm`.** The core reads
 * `quality-set` unconfirmed as a choice it records or holds, and confirmed as
 * a choice made over the hold. Both methods reach the same name and differ by
 * that one value, spelled once, in a named private method no caller holds.
 *
 * **Which answer came back is the stack's to say.** A preset is answered with
 * the whole of what is in force and a format for music with what its service
 * made of it, so the envelope's kind decides which is read — not the kind of
 * media that was asked for, which would be this app deciding what the stack
 * answers with.
 */
final readonly class Graders implements ChoosingQuality
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function inForceOn(Stack $stack, Session $session): WhatWasFoundOfTheQuality
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::QUALITY_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheQuality::found(WhatIsChosen::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheQuality::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|QualityIsUnreadable|QualitySaysNothing $why) {
            return WhatWasFoundOfTheQuality::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function choose(Stack $stack, Session $session, APresetToChoose $asked): WhatTheChoiceCameTo
    {
        return $this->asking($stack, $session, $asked, confirmed: false);
    }

    public function confirm(Stack $stack, Session $session, AHeldChoice $agreed): WhatTheChoiceCameTo
    {
        return $this->asking($stack, $session, $agreed->asked(), confirmed: true);
    }

    /**
     * The one call both methods make.
     *
     * `confirmed` is a named argument at both call sites above, which is the
     * whole protection: the two lines that decide whether a held choice is
     * made read as `confirmed: false` and `confirmed: true`.
     */
    private function asking(Stack $stack, Session $session, APresetToChoose $asked, bool $confirmed): WhatTheChoiceCameTo
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                $this->body($asked, $confirmed),
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            // Inside the same `try` as the call, for {@see Adjustments}'
            // reason: on a write, *did it happen* is the question, and an
            // answer this side could not read does not say.
            return $this->cameTo($envelope);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheChoiceCameTo::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|QualityIsUnreadable|QualitySaysNothing $why) {
            return WhatTheChoiceCameTo::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What is put on the wire.
     *
     * A choice for everything names no kind of media, which is how the stack
     * tells the two apart.
     */
    private function body(APresetToChoose $asked, bool $confirmed): QualitySetAction
    {
        return new QualitySetAction(
            preset: $asked->preset(),
            mediaType: $asked->isForEverything() ? null : $asked->kind(),
            confirm: $confirmed,
        );
    }

    /**
     * The answer, read by whichever reader its kind names.
     *
     * @param Envelope<mixed> $envelope
     */
    private function cameTo(Envelope $envelope): WhatTheChoiceCameTo
    {
        return $envelope->kind === MusicEnvelope::KIND->value
            ? WhatTheChoiceCameTo::forMusic(WhatMusicCameTo::in($envelope))
            : WhatTheChoiceCameTo::inForce(WhatIsChosen::in($envelope));
    }
}
