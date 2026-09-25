<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Events\SseParser;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\DashboardEnvelope;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\SummaryCountsBelowNothing;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Sdk\Internal\AStreamHeldOpen;
use Modules\Sdk\Internal\WhatARefusalMeant;

use function sprintf;

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
 * once. Each call takes chunks until one comes back empty, reads the last
 * `dashboard` event among them, and hands that back. The wait for the next
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
 * cadence it states.
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
            return $this->taken($this->held ??= $this->opened($stack, $session));
        } catch (RequestFailed $why) {
            return WhatWasHeard::met(WhatARefusalMeant::obstacle($why));
        } catch (
            Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted
            |SummaryIsUnreadable|CheckIsUnnamed|RemedySaysNothing|SummaryCountsBelowNothing
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

    /**
     * Everything that has arrived, up to the first read that found nothing.
     *
     * A chunk is taken and the reader moved on before the chunk is looked at,
     * so the read that comes back empty is the last one this call waits on and
     * whatever the move found is the first thing the next call takes. A stream
     * that runs out before any read comes back empty has ended.
     */
    private function taken(AStreamHeldOpen $held): WhatWasHeard
    {
        $arrived = '';

        while ($held->chunks->valid()) {
            $chunk = $held->chunks->current();
            $held->chunks->next();

            if ($chunk === '') {
                return $this->heard($held, $arrived, ended: false);
            }

            $arrived = sprintf('%s%s', $arrived, $chunk);
        }

        return $this->heard($held, $arrived, ended: true);
    }

    /** What arrived, as what was heard, letting go of a stream that ended. */
    private function heard(AStreamHeldOpen $held, string $arrived, bool $ended): WhatWasHeard
    {
        $latest = $this->theLastSummaryIn($held->parser->feed($arrived));

        if ($ended) {
            $this->held = null;
            $this->ended = $arrived !== '';
        }

        return match (true) {
            $latest instanceof ServerEvent => WhatWasHeard::said(Summaries::in(new EnvelopeReader()->read($latest->data))),
            $arrived !== '' => WhatWasHeard::aSignOfLife(),
            $ended => WhatWasHeard::closed(),
            default => WhatWasHeard::nothing(),
        };
    }

    /** @param list<ServerEvent> $events */
    private function theLastSummaryIn(array $events): ?ServerEvent
    {
        $latest = null;

        foreach ($events as $event) {
            if ($event->kind === DashboardEnvelope::KIND->value) {
                $latest = $event;
            }
        }

        return $latest;
    }
}
