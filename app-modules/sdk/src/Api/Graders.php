<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\MusicEnvelope;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhatToDoAboutQuality;
use Modules\Kernel\Api\WhatWasFoundOfTheQuality;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\Fields\UpgradeField;
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
    public function __construct(private Clients $clients) {}

    public function inForceOn(Stack $stack, Session $session): WhatWasFoundOfTheQuality
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::QUALITY_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheQuality::found(WhatIsChosen::in($envelope));
        } catch (RequestFailed $why) {
            return WhatWasFoundOfTheQuality::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|QualityIsUnreadable|QualitySaysNothing) {
            return WhatWasFoundOfTheQuality::met(Obstacle::StackDidNotAnswer);
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
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(Api::action(WhatToDoAboutQuality::Choose->asked()), $this->body($asked, $confirmed));

            // Inside the same `try` as the call, for {@see Adjustments}'
            // reason: on a write, *did it happen* is the question, and an
            // answer this side could not read does not say.
            return $this->cameTo($envelope);
        } catch (RequestFailed $why) {
            return WhatTheChoiceCameTo::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|QualityIsUnreadable|QualitySaysNothing) {
            return WhatTheChoiceCameTo::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * What is put on the wire.
     *
     * A choice for everything leaves `media_type` out rather than sending it
     * blank, which is how the stack tells the two apart.
     *
     * @return array<string, bool|string>
     */
    private function body(APresetToChoose $asked, bool $confirmed): array
    {
        // One literal rather than keys written into a table afterwards: a
        // subscript on the left of an assignment reads to the wire register
        // as a field being reached for.
        return [
            WireField::Preset->value => $asked->preset(),
            ...($asked->isForEverything() ? [] : [UpgradeField::MediaType->value => $asked->kind()]),
            UpdateField::Confirm->value => $confirmed,
        ];
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
