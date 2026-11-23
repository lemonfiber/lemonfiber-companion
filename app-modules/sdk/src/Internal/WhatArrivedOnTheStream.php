<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

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
 */
final readonly class WhatArrivedOnTheStream
{
    /** @param list<ServerEvent> $events */
    public function __construct(
        private array $events,
        public bool $anything,
        public bool $ended,
    ) {}

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
}
