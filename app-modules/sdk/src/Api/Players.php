<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\PlayingEnvelope;
use Modules\Kernel\Api\FollowingWhatIsPlaying;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SessionPlayingSaysNothing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatArrivedOnTheStream;
use Override;

/**
 * What the household is playing: read from the core's `/api/playing`, then heard as the `playing` event on the stack's stream.
 *
 * Built as {@see Narrators} is, and mutable for its reason: one connection held
 * for one screen, resumed where the last one left off, and said to have ended
 * once.
 */
final class Players implements FollowingWhatIsPlaying
{
    private bool $ended = false;

    private readonly TheStreamsHeld $streams;

    public function __construct(private readonly Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    #[Override]
    public function now(Stack $stack, Session $session): HowPlayingWasRead
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowPlayingWasRead::said(WhatThePlayingSaid::in($client->read(Api::PLAYING_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowPlayingWasRead::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|PlayingIsUnreadable|SessionPlayingSaysNothing $why) {
            return HowPlayingWasRead::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** @return WhatAStreamSaid<WhatTheHouseholdIsPlaying> */
    #[Override]
    public function heard(Stack $stack, Session $session): WhatAStreamSaid
    {
        if ($this->ended) {
            $this->ended = false;

            return WhatAStreamSaid::closed();
        }

        return $this->heardFrom($stack, $session);
    }

    /** @return WhatAStreamSaid<WhatTheHouseholdIsPlaying> */
    #[Override]
    public function letGo(): WhatAStreamSaid
    {
        $this->streams->letGoOfEvery();
        $this->ended = false;

        return WhatAStreamSaid::closed();
    }

    /**
     * What has arrived on the stream, opening it where it is not open.
     *
     * @return WhatAStreamSaid<WhatTheHouseholdIsPlaying>
     */
    private function heardFrom(Stack $stack, Session $session): WhatAStreamSaid
    {
        try {
            return $this->arrived($stack, $this->streams->taken($stack, $session));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatAStreamSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|PlayingIsUnreadable|SessionPlayingSaysNothing $why) {
            $this->letGoOf($stack);

            return WhatAStreamSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function letGoOf(Stack $stack): void
    {
        $this->streams->letGoOf($stack);
        $this->ended = false;
    }

    /**
     * What arrived, as what the household is playing, letting go of a stream that ended.
     *
     * @return WhatAStreamSaid<WhatTheHouseholdIsPlaying>
     */
    private function arrived(Stack $stack, WhatArrivedOnTheStream $arrived): WhatAStreamSaid
    {
        $latest = $arrived->theLast(PlayingEnvelope::KIND);

        if ($arrived->ended) {
            $this->letGoOf($stack);
            $this->ended = $arrived->anything;
        }

        return match (true) {
            $latest instanceof ServerEvent => WhatAStreamSaid::said(WhatThePlayingSaid::in(new EnvelopeReader()->read($latest->data))),
            $arrived->anything => WhatAStreamSaid::aSignOfLife(),
            $arrived->ended => WhatAStreamSaid::closed(),
            default => WhatAStreamSaid::nothing(),
        };
    }
}
