<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\StepEnvelope;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkFollowed;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatArrivedOnTheStream;
use Modules\Sdk\Internal\WhoseLines;

/**
 * The steps a running walkthrough says, heard on a stack's event stream.
 *
 * {@see Listeners} for the other thing said there, and built the same way:
 * through the SDK, pinned like every other connection, never waiting on the
 * stack, and holding one connection for one screen. What it hands back is the
 * last `step` the walk it follows said among everything that arrived since the
 * screen last asked, read by {@see Walkthroughs::said()} into the line the
 * finished record would carry.
 *
 * **Mutable, for {@see Listeners}' reason.** What it keeps between calls is the
 * connection itself, in {@see TheStreamsHeld}, and whether it ended, which is
 * why it is bound fresh for each screen.
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
    private bool $ended = false;

    private readonly TheStreamsHeld $streams;

    public function __construct(private readonly Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    /** @return WhatAStreamSaid<ALineItSaid> */
    public function whereItIs(Stack $stack, Session $session, AWalkFollowed $walk): WhatAStreamSaid
    {
        if ($this->ended) {
            $this->ended = false;

            return WhatAStreamSaid::closed();
        }

        return $this->heardFrom($stack, $session, $walk);
    }

    /** @return WhatAStreamSaid<ALineItSaid> */
    public function letGo(): WhatAStreamSaid
    {
        $this->streams->letGoOfEvery();
        $this->ended = false;

        return WhatAStreamSaid::closed();
    }

    /**
     * What has arrived on the stream, opening it where it is not open.
     *
     * @return WhatAStreamSaid<ALineItSaid>
     */
    private function heardFrom(Stack $stack, Session $session, AWalkFollowed $walk): WhatAStreamSaid
    {
        try {
            return $this->heard($stack, $this->streams->taken($stack, $session), $walk);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatAStreamSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|WalkthroughIsUnreadable|EnvelopeIsNotRead|JobHasNoName $why) {
            $this->letGoOf($stack);

            return WhatAStreamSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** Let go of the stream, keeping where it left off for the stream opened in its place. */
    private function letGoOf(Stack $stack): void
    {
        $this->streams->letGoOf($stack);
        $this->ended = false;
    }

    /**
     * What arrived, as what the walk said, letting go of a stream that ended.
     *
     * @return WhatAStreamSaid<ALineItSaid>
     */
    private function heard(Stack $stack, WhatArrivedOnTheStream $arrived, AWalkFollowed $walk): WhatAStreamSaid
    {
        $latest = $arrived->theLastSaidBy(WhoseLines::ofTheWalk($walk), StepEnvelope::KIND);

        if ($arrived->ended) {
            $this->letGoOf($stack);
            $this->ended = $arrived->anything;
        }

        return match (true) {
            $latest instanceof Envelope => WhatAStreamSaid::said(Walkthroughs::said($latest)),
            $arrived->anything => WhatAStreamSaid::aSignOfLife(),
            $arrived->ended => WhatAStreamSaid::closed(),
            default => WhatAStreamSaid::nothing(),
        };
    }
}
