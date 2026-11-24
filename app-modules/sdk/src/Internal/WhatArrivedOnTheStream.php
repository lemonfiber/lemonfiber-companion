<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_first;
use function array_last;
use function array_map;
use function in_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Generated\Kind;

/**
 * What one look at a held stream found: the events, whether anything came at all, and whether the stream ended.
 *
 * The three are apart because a screen does something different with each. A
 * heartbeat is bytes and no event, and it proves the stack is still there. An
 * event of a kind the screen is not holding the stream for proves the same. A
 * stream that ran out is closed, whatever it said before it did.
 *
 * **The first events of a stream opened again from an id say whether every
 * alert came back.** A stack answering that opens with one `replay` event,
 * and only one that says `whole` vouches for what follows. One that says
 * `partial`, one that cannot be read, and a stream that opens with anything
 * else, which is how a stack too old to say so answers, all leave some alerts
 * that may be missing.
 */
final readonly class WhatArrivedOnTheStream
{
    /** @param list<ServerEvent> $events */
    public function __construct(
        private array $events,
        public bool $anything,
        public bool $ended,
        public bool $someAlertsMayBeMissing = false,
    ) {}

    /** Whether an event arrived, rather than bytes alone or nothing. */
    public function heldAnEvent(): bool
    {
        return $this->events !== [];
    }

    /** The same, read as the first events of a stream opened again from an id. */
    public function asTheFirstAfterAnId(): self
    {
        return $this->opensWithEveryAlert() ? $this : new self($this->events, $this->anything, $this->ended, someAlertsMayBeMissing: true);
    }

    /** The last event of one kind among everything that arrived, where one did. */
    public function theLast(Kind $kind): ?ServerEvent
    {
        $latest = null;

        foreach ($this->events as $event) {
            if ($event->kind === $kind->value) {
                $latest = $event;
            }
        }

        return $latest;
    }

    /**
     * Every event of one kind, in the order they arrived.
     *
     * @return list<ServerEvent>
     */
    public function every(Kind $kind): array
    {
        $every = [];

        foreach ($this->events as $event) {
            if ($event->kind === $kind->value) {
                $every[] = $event;
            }
        }

        return $every;
    }

    /**
     * The envelope of the last event of one kind among everything that arrived, said by whose lines it takes, where one was.
     *
     * @return Envelope<mixed>|null
     */
    public function theLastSaidBy(WhoseLines $whose, Kind $kind): ?Envelope
    {
        return array_last($this->everySaidBy($whose, $kind));
    }

    /**
     * The envelope of every event of these kinds said by whose lines it takes, in the order they arrived.
     *
     * @return list<Envelope<mixed>>
     */
    public function everySaidBy(WhoseLines $whose, Kind ...$kinds): array
    {
        $names = array_map(static fn(Kind $kind): string => $kind->value, $kinds);
        $every = [];

        foreach ($this->events as $event) {
            if (! in_array($event->kind, $names, strict: true)) {
                continue;
            }

            $envelope = new EnvelopeReader()->read($event->data);

            if ($whose->take($envelope->job)) {
                $every[] = $envelope;
            }
        }

        return $every;
    }

    /** Whether the first event is a `replay` saying every alert raised since the id follows. */
    private function opensWithEveryAlert(): bool
    {
        $first = array_first($this->events);

        return $first instanceof ServerEvent && WhatTheReplaySaid::everyAlertFollows($first);
    }
}
