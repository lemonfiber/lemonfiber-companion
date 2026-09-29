<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_string;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Events\SseParser;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\DashboardEnvelope;
use Lemonfiber\Sdk\Generated\StartEnvelope;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HowLongIsBelowNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StartSaysNothing;
use Modules\Kernel\Api\StoppageSaysNothing;
use Modules\Kernel\Api\SummaryCountsBelowNothing;
use Modules\Kernel\Api\WhatAStartWaitsOn;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Sdk\Internal\AStreamHeldOpen;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application holds a stack's event stream open.
 *
 * Through the SDK like every other call: the connection comes from
 * {@see Clients}, so it is pinned like every other, and the bytes are split
 * into events by the SDK's own parser and read by the SDK's own envelope
 * reader. What this adds is the one thing the SDK's feed cannot do on a
 * runtime with a single thread, which is to take what has arrived and return
 * without waiting for more.
 *
 * **It never waits on the stack.** The stream is opened with a wait of one
 * millisecond, so a read that finds nothing comes back with nothing almost at
 * once. Each call takes what {@see AStreamHeldOpen::taken()} finds, reads the
 * last `dashboard` event among it, and hands that back. The wait for the next
 * event is spent between calls, on the screen's cadence, rather than inside
 * one.
 *
 * **Mutable, because a held connection is.** This is the one adapter that
 * keeps something between calls, and what it keeps is the connection itself.
 * One belongs to one screen, which is why it is bound fresh for each.
 *
 * **Every refusal is an obstacle.** The stream refusing the session, the two
 * ends disagreeing about the version, an event that is not an envelope, and a
 * summary this app cannot read all leave the screen with the same thing to
 * say: this stack could not be heard. Nothing is held after any of them, so
 * the next attempt opens a new connection.
 *
 * **A stream that ends is said to have ended once.** The call that finds the
 * end hands back what arrived before it, and the next hands back `closed`
 * without opening anything. The screen decides when to open again, on the
 * cadence it declares.
 *
 * **A `ConfigurationProblem` is deliberately not caught**, for {@see Admissions}'
 * reason: the SDK raises one where a stored stack cannot be pinned, which is a
 * fault in what this app retained rather than a stack that could not be heard.
 */
final class Listeners implements Hearing
{
    /**
     * How long a read may wait for bytes that have not arrived, in milliseconds.
     *
     * The least the SDK accepts, because the wait for the next event belongs
     * between a screen's wakes and not inside one.
     */
    public const int NO_LONGER_THAN_MS = 1;

    private ?AStreamHeldOpen $held = null;

    private bool $ended = false;

    public function __construct(private readonly Clients $clients) {}

    public function howItIs(Stack $stack, Session $session): WhatWasHeard
    {
        if ($this->ended) {
            $this->ended = false;

            return WhatWasHeard::closed();
        }

        return $this->heardFrom($stack, $session);
    }

    public function whatAStartWaitsOn(Stack $stack, Session $session): WhatAStartWaitsOn
    {
        try {
            $held = $this->held ??= $this->opened($stack, $session);
            $arrived = $held->taken();
            $latest = $arrived->theLast(StartEnvelope::KIND);

            // A stream that ended is let go of and opened again on the next
            // ask. There is no end to report here: a start's lines stop when
            // the start does, and the job being followed says when that was.
            if ($arrived->ended) {
                $this->held = null;
            }

            return $latest instanceof ServerEvent
                ? WhatAStartWaitsOn::saying($this->lineIn($latest))
                : WhatAStartWaitsOn::nothingNew();
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatAStartWaitsOn::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|StartSaysNothing) {
            $this->letGo();

            return WhatAStartWaitsOn::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function letGo(): WhatWasHeard
    {
        $this->held = null;
        $this->ended = false;

        return WhatWasHeard::closed();
    }

    /** What has arrived on the stream, opening it where it is not open. */
    private function heardFrom(Stack $stack, Session $session): WhatWasHeard
    {
        try {
            return $this->heard($this->held ??= $this->opened($stack, $session));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasHeard::met(WhatARefusalMeant::obstacle($why));
        } catch (
            Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted
            |SummaryIsUnreadable|CheckIsUnnamed|RemedySaysNothing|SummaryCountsBelowNothing|StoppageSaysNothing|HowLongIsBelowNothing
        ) {
            $this->letGo();

            return WhatWasHeard::met(Obstacle::StackDidNotAnswer);
        }
    }

    private function opened(Stack $stack, Session $session): AStreamHeldOpen
    {
        return new AStreamHeldOpen(
            $this->clients->client($stack, $session)
                ->eventSource(Duration::ofMilliseconds(self::NO_LONGER_THAN_MS))
                ->open(null),
            new SseParser(),
        );
    }

    /** What arrived, as what was heard, letting go of a stream that ended. */
    private function heard(AStreamHeldOpen $held): WhatWasHeard
    {
        $arrived = $held->taken();
        $latest = $arrived->theLast(DashboardEnvelope::KIND);

        if ($arrived->ended) {
            $this->held = null;
            $this->ended = $arrived->anything;
        }

        return match (true) {
            $latest instanceof ServerEvent => WhatWasHeard::said(Summaries::in(new EnvelopeReader()->read($latest->data))),
            $arrived->anything => WhatWasHeard::aSignOfLife(),
            $arrived->ended => WhatWasHeard::closed(),
            default => WhatWasHeard::nothing(),
        };
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
