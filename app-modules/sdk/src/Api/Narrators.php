<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

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
use Lemonfiber\Sdk\Generated\StepEnvelope;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Modules\Sdk\Internal\AStreamHeldOpen;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhereTheStreamsLeftOff;

/**
 * The steps a running walkthrough says, heard on a stack's event stream.
 *
 * {@see Listeners} for the other thing said there, and built the same way:
 * through the SDK, pinned like every other connection, never waiting on the
 * stack, and holding one connection for one screen. What it hands back is the
 * last `step` among everything that arrived since the screen last asked, read
 * by {@see Walkthroughs::said()} into the line the finished record would carry.
 *
 * **Mutable, for {@see Listeners}' reason.** What it keeps between calls is the
 * connection itself, which is why it is bound fresh for each screen.
 *
 * **Every refusal is an obstacle, and a step it cannot read is one too.** The
 * stream refusing the session, the two ends disagreeing about the version, an
 * event that is not an envelope and a step this app cannot read all leave the
 * screen unable to say which stage the walk is at. Nothing is held after any of
 * them, so the next attempt opens a new connection.
 *
 * **A stream opened again resumes where the last one left off**, for
 * {@see Listeners}' reason: a step said while no stream was open is heard
 * rather than skipped.
 *
 * **A stream that ends is said to have ended once.** The call that finds the
 * end hands back what arrived before it, and the next hands back `closed`
 * without opening anything.
 */
final class Narrators implements HearingTheWalk
{
    private ?AStreamHeldOpen $held = null;

    private bool $ended = false;

    private WhereTheStreamsLeftOff $leftOff;

    public function __construct(private readonly Clients $clients)
    {
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();
    }

    public function whereItIs(Stack $stack, Session $session): WhatTheWalkSaid
    {
        if ($this->ended) {
            $this->ended = false;

            return WhatTheWalkSaid::closed();
        }

        return $this->heardFrom($stack, $session);
    }

    public function letGo(): WhatTheWalkSaid
    {
        $this->held = null;
        $this->ended = false;
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();

        return WhatTheWalkSaid::closed();
    }

    /** What has arrived on the stream, opening it where it is not open. */
    private function heardFrom(Stack $stack, Session $session): WhatTheWalkSaid
    {
        try {
            return $this->heard($stack, $this->held ??= $this->opened($stack, $session));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheWalkSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|WalkthroughIsUnreadable $why) {
            $this->letGoOf($stack);

            return WhatTheWalkSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * The same stream {@see Listeners} holds, opened with the same wait, and
     * resuming after the last event this stack's previous stream carried.
     */
    private function opened(Stack $stack, Session $session): AStreamHeldOpen
    {
        $after = $this->leftOff->forTheStack($stack->id()->stored());

        return new AStreamHeldOpen(
            $this->clients->client($stack, $session)
                ->eventSource(Duration::ofMilliseconds(Listeners::NO_LONGER_THAN_MS))
                ->open($after),
            new SseParser($after),
        );
    }

    /** Let go of the stream, keeping where it left off for the stream opened in its place. */
    private function letGoOf(Stack $stack): void
    {
        $this->leftOff = $this->leftOff->keeping($stack->id()->stored(), $this->held);
        $this->held = null;
        $this->ended = false;
    }

    /** What arrived, as what the walk said, letting go of a stream that ended. */
    private function heard(Stack $stack, AStreamHeldOpen $held): WhatTheWalkSaid
    {
        $arrived = $held->taken();
        $latest = $arrived->theLast(StepEnvelope::KIND);

        if ($arrived->ended) {
            $this->letGoOf($stack);
            $this->ended = $arrived->anything;
        }

        return match (true) {
            $latest instanceof ServerEvent => WhatTheWalkSaid::said(Walkthroughs::said(new EnvelopeReader()->read($latest->data))),
            $arrived->anything => WhatTheWalkSaid::aSignOfLife(),
            $arrived->ended => WhatTheWalkSaid::closed(),
            default => WhatTheWalkSaid::nothing(),
        };
    }
}
