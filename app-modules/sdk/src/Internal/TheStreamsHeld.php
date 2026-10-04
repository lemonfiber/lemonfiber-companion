<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Lemonfiber\Sdk\Events\SseParser;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\Listeners;

/**
 * Each stack's event stream held open for one screen, and where each let go of left off.
 *
 * What every adapter reading the stream shares: a connection per stack, opened
 * pinned through {@see Clients} with a wait of {@see Listeners::NO_LONGER_THAN_MS},
 * kept between calls, and resuming after the last event the stream before it
 * carried. What each adapter reads out of what arrived is its own.
 *
 * **Mutable, because a held connection is**, for {@see Listeners}' reason: it
 * is what keeps the connection between one wake of a screen and the next.
 */
final class TheStreamsHeld
{
    /** @var array<string, AStreamHeldOpen> each stack's open stream, by its stored identifier */
    private array $held = [];

    private WhereTheStreamsLeftOff $leftOff;

    public function __construct(private readonly Clients $clients)
    {
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();
    }

    /** What has arrived on this stack's stream since it was last asked, opening it where it is not open. */
    public function taken(Stack $stack, Session $session): WhatArrivedOnTheStream
    {
        return ($this->held[$stack->id()->stored()] ??= $this->opened($stack, $session))->taken();
    }

    /** Let go of one stack's stream, keeping where it left off for the stream opened in its place. */
    public function letGoOf(Stack $stack): void
    {
        $which = $stack->id()->stored();

        if (array_key_exists($which, $this->held)) {
            $this->leftOff = $this->leftOff->keeping($which, $this->held[$which]);
        }

        unset($this->held[$which]);
    }

    /** Let go of every stream, forgetting where each left off. */
    public function letGoOfEvery(): void
    {
        $this->held = [];
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();
    }

    /** A stream for this stack, resuming after the last event its previous one carried. */
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
}
