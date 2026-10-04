<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\DashboardEnvelope;
use Lemonfiber\Sdk\Generated\NewsEnvelope;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HowLongIsBelowNothing;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\RequestIsUnnumbered;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StoppageSaysNothing;
use Modules\Kernel\Api\SummaryCountsBelowNothing;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatArrivedOnTheStream;

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
 * last `dashboard` event among it, and hands that back, with the last `news`
 * event beside it where one arrived: the newest of each kind the stack names,
 * which it says when a listener arrives and whenever it changes. The wait for
 * the next event is spent between calls, on the screen's cadence, rather than
 * inside one.
 *
 * **Mutable, because a held connection is.** What it keeps between calls is
 * the connection itself, one for each stack it is asked about, held in
 * {@see TheStreamsHeld} as {@see Narrators} and {@see StartLines} hold theirs.
 * One belongs to one screen, which is why it is bound fresh for each: the
 * screen for one stack holds one stream, and the list of stacks holds one per
 * stack.
 *
 * **Every refusal is an obstacle.** The stream refusing the session, the two
 * ends disagreeing about the version, an event that is not an envelope, and a
 * summary this app cannot read all leave the screen with the same thing to
 * say: this stack could not be heard. Nothing is held for that stack after
 * any of them, so the next attempt opens a new connection, and every other
 * stack's stream stays open.
 *
 * **A stream opened again resumes where the last one left off.** Where the
 * stream let go of carried event identifiers, the one opened in its place asks
 * for what came after the last of them, so what the stack said in between is
 * heard rather than skipped. Letting go of every stream forgets where each left
 * off.
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

    /** @var array<string, true> the stacks whose stream ended and has not been said to have ended */
    private array $ended = [];

    private readonly TheStreamsHeld $streams;

    public function __construct(private readonly Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    public function howItIs(Stack $stack, Session $session): WhatWasHeard
    {
        $which = $stack->id()->stored();

        if (array_key_exists($which, $this->ended)) {
            unset($this->ended[$which]);

            return WhatWasHeard::closed();
        }

        return $this->heardFrom($stack, $session);
    }

    public function letGo(): WhatWasHeard
    {
        $this->streams->letGoOfEvery();
        $this->ended = [];

        return WhatWasHeard::closed();
    }

    /** What has arrived on the stream, opening it where it is not open. */
    private function heardFrom(Stack $stack, Session $session): WhatWasHeard
    {
        try {
            return $this->heard($stack, $this->streams->taken($stack, $session));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasHeard::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|SummaryIsUnreadable|CheckIsUnnamed|RemedySaysNothing|SummaryCountsBelowNothing|StoppageSaysNothing|HowLongIsBelowNothing|NewsIsUnreadable|VersionIsBlank|RequestIsUnnumbered $why) {
            $this->letGoOf($stack);

            return WhatWasHeard::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** Let go of one stack's stream, leaving every other stack's open. */
    private function letGoOf(Stack $stack): void
    {
        $this->streams->letGoOf($stack);
        unset($this->ended[$stack->id()->stored()]);
    }

    /** What arrived, as what was heard, letting go of a stream that ended. */
    private function heard(Stack $stack, WhatArrivedOnTheStream $arrived): WhatWasHeard
    {
        $latest = $arrived->theLast(DashboardEnvelope::KIND);
        $newest = $arrived->theLast(NewsEnvelope::KIND);

        if ($arrived->ended) {
            $this->letGoOf($stack);
        }

        if ($arrived->ended && $arrived->anything) {
            $this->ended[$stack->id()->stored()] = true;
        }

        $heard = match (true) {
            $latest instanceof ServerEvent => WhatWasHeard::said(Summaries::in(new EnvelopeReader()->read($latest->data))),
            $arrived->anything => WhatWasHeard::aSignOfLife(),
            $arrived->ended => WhatWasHeard::closed(),
            default => WhatWasHeard::nothing(),
        };

        return $newest instanceof ServerEvent ? $heard->naming(WhatIsNamedAsNewest::in(new EnvelopeReader()->read($newest->data))) : $heard;
    }
}
