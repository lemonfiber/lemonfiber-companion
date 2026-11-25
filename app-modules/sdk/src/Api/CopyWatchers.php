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
use Lemonfiber\Sdk\Generated\CopyingEnvelope;
use Modules\Kernel\Api\FollowingTheCopyRunning;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatArrivedOnTheStream;
use Override;

/**
 * The backup or restore running on a stack: read from the core's `/api/copying`, then heard as the `copying` event on the stack's stream.
 *
 * Built as {@see Players} is, and mutable for its reason: one connection held
 * for one screen, resumed where the last one left off, and said to have ended
 * once.
 */
final class CopyWatchers implements FollowingTheCopyRunning
{
    private bool $ended = false;

    private readonly TheStreamsHeld $streams;

    public function __construct(private readonly Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    #[Override]
    public function now(Stack $stack, Session $session): HowTheCopyRunningWasRead
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return HowTheCopyRunningWasRead::said(WhatTheCopyingSaid::in($client->read(Api::COPYING_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowTheCopyRunningWasRead::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|CopyingIsUnreadable $why) {
            return HowTheCopyRunningWasRead::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
    #[Override]
    public function heard(Stack $stack, Session $session): WhatAStreamSaid
    {
        if ($this->ended) {
            $this->ended = false;

            return WhatAStreamSaid::closed();
        }

        return $this->heardFrom($stack, $session);
    }

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
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
     * @return WhatAStreamSaid<WhatIsBeingCopied>
     */
    private function heardFrom(Stack $stack, Session $session): WhatAStreamSaid
    {
        try {
            return $this->arrived($stack, $this->streams->taken($stack, $session));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatAStreamSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|CopyingIsUnreadable $why) {
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
     * What arrived, as what is being copied, letting go of a stream that ended.
     *
     * @return WhatAStreamSaid<WhatIsBeingCopied>
     */
    private function arrived(Stack $stack, WhatArrivedOnTheStream $arrived): WhatAStreamSaid
    {
        $latest = $arrived->theLast(CopyingEnvelope::KIND);

        if ($arrived->ended) {
            $this->letGoOf($stack);
            $this->ended = $arrived->anything;
        }

        return match (true) {
            $latest instanceof ServerEvent => WhatAStreamSaid::said(WhatTheCopyingSaid::in(new EnvelopeReader()->read($latest->data))),
            $arrived->anything => WhatAStreamSaid::aSignOfLife(),
            $arrived->ended => WhatAStreamSaid::closed(),
            default => WhatAStreamSaid::nothing(),
        };
    }
}
