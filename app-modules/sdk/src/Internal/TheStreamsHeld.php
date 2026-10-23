<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Lemonfiber\Sdk\Events\SseParser;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\EventHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhereHearingLeftOff;
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
 * **Where it is handed a {@see WhereHearingLeftOff}, where each stream got to
 * outlives it.** Every wake that carried an event notes the last one there,
 * and a stream opens after what is noted, so letting go of every stream, or
 * the app closing, loses nothing a stack still holds. Without one, where a
 * stream left off lasts until every stream is let go of.
 *
 * **Mutable, because a held connection is**, for {@see Listeners}' reason: it
 * is what keeps the connection between one wake of a screen and the next.
 */
final class TheStreamsHeld
{
    /** @var array<string, AStreamHeldOpen> each stack's open stream, by its stored identifier */
    private array $held = [];

    /** @var array<string, string> the last event noted as heard to, by each stack's stored identifier */
    private array $noted = [];

    private WhereTheStreamsLeftOff $leftOff;

    public function __construct(private readonly Clients $clients, private readonly ?WhereHearingLeftOff $kept = null)
    {
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();
    }

    /** What has arrived on this stack's stream since it was last asked, opening it where it is not open. */
    public function taken(Stack $stack, Session $session): WhatArrivedOnTheStream
    {
        $held = $this->held[$stack->id()->stored()] ??= $this->opened($stack, $session);
        $arrived = $held->taken();
        $this->noteWhereItGotTo($stack, $held->leftOffAt());

        return $arrived;
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

    /** Let go of every stream, forgetting where each left off but for what is noted as heard to. */
    public function letGoOfEvery(): void
    {
        $this->held = [];
        $this->leftOff = WhereTheStreamsLeftOff::nowhere();
    }

    /** A stream for this stack, resuming after the last event noted for it, or the last its previous one carried. */
    private function opened(Stack $stack, Session $session): AStreamHeldOpen
    {
        $after = $this->resumingAfter($stack);

        if ($after !== null) {
            $this->noted[$stack->id()->stored()] = $after;
        }

        return new AStreamHeldOpen(
            $this->clients->client($stack, $session)
                ->eventSource(Duration::ofMilliseconds(Listeners::NO_LONGER_THAN_MS))
                ->open($after),
            new SseParser($after),
        );
    }

    /** The event noted as heard to for this stack, or where its previous stream left off where none is noted. */
    private function resumingAfter(Stack $stack): ?string
    {
        $leftOff = $this->leftOff->forTheStack($stack->id()->stored());

        if (! $this->kept instanceof WhereHearingLeftOff) {
            return $leftOff;
        }

        return $this->kept->leftOffAt($stack->id())->either(
            after: static fn(AnEventHeard $event): TheEventToResumeAfter => new TheEventToResumeAfter($event->shown()),
            fromTheStart: static fn(): TheEventToResumeAfter => new TheEventToResumeAfter($leftOff),
        )->event;
    }

    /** Note the last event this stack's stream carried, where there is somewhere to note it and it moved. */
    private function noteWhereItGotTo(Stack $stack, ?string $at): void
    {
        if (! $this->kept instanceof WhereHearingLeftOff || $at === null) {
            return;
        }

        $which = $stack->id()->stored();

        if (array_key_exists($which, $this->noted) && $this->noted[$which] === $at) {
            return;
        }

        try {
            $event = AnEventHeard::named($at);
        } catch (EventHasNoName) {
            return;
        }

        if ($this->kept->heardTo($stack->id(), $event)) {
            $this->noted[$which] = $at;
        }
    }
}
