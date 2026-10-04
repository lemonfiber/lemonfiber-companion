<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_string;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\StartEnvelope;
use Modules\Kernel\Api\HearingTheStart;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StartSaysNothing;
use Modules\Kernel\Api\WhatAStartWaitsOn;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * What a running start is waiting for, heard on a stack's event stream.
 *
 * {@see Listeners} for the other thing said there, and built the same way:
 * through the SDK, pinned like every other connection, never waiting on the
 * stack, and holding one connection for one screen. What it hands back is the
 * last `start` line among everything that arrived since the screen last asked.
 *
 * **It keeps the connection between calls**, in {@see TheStreamsHeld}, which
 * is why it is bound fresh for each screen.
 *
 * **Every refusal is an obstacle, and a line it cannot read is one too.** The
 * stream refusing the session, the two ends disagreeing about the version, an
 * event that is not an envelope and a line that is not text all leave the
 * screen with nothing new to draw. Nothing is held after a stream that broke
 * or a line it could not read, so the next ask opens a new connection.
 *
 * **A stream that ends is let go of and opened again on the next ask**,
 * resuming after the last event it carried. There is no end to report: a
 * start's lines stop when the start does, and the job being followed says when
 * that was.
 */
final readonly class StartLines implements HearingTheStart
{
    private TheStreamsHeld $streams;

    public function __construct(private Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    public function whatItWaitsOn(Stack $stack, Session $session): WhatAStartWaitsOn
    {
        try {
            $arrived = $this->streams->taken($stack, $session);
            $latest = $arrived->theLast(StartEnvelope::KIND);

            if ($arrived->ended) {
                $this->streams->letGoOf($stack);
            }

            return $latest instanceof ServerEvent
                ? WhatAStartWaitsOn::saying($this->lineIn($latest))
                : WhatAStartWaitsOn::nothingNew();
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatAStartWaitsOn::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|StartSaysNothing $why) {
            $this->streams->letGoOf($stack);

            return WhatAStartWaitsOn::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function letGo(): WhatAStartWaitsOn
    {
        $this->streams->letGoOfEvery();

        return WhatAStartWaitsOn::nothingNew();
    }

    /**
     * The sentence a start line carries, refusing one that is not text.
     *
     * The envelope's payload is a bare string, and the generated reader
     * asserts that without checking it, so it is checked here.
     */
    private function lineIn(ServerEvent $event): string
    {
        $said = $this->payloadOf($event);

        if (! is_string($said)) {
            throw StartSaysNothing::inItsLine();
        }

        return $said;
    }

    /**
     * A start line's payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Origins::payload()}'s reason: the
     * generated envelope asserts its shape without checking it.
     */
    private function payloadOf(ServerEvent $event): mixed
    {
        $envelope = new EnvelopeReader()->read($event->data);

        return StartEnvelope::in($envelope)->data;
    }
}
