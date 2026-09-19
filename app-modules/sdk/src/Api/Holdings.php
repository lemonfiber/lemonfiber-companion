<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HeldEnvelope;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\WhatTheyMayWatch;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Sdk\Internal\Wire;

/**
 * The `held` envelope, read into what a screen can draw.
 *
 * {@see Tellings} one endpoint over, and the same argument: the reading is a
 * separate thing from the port so that what a payload means is decided in one
 * place, and the adapter is left holding only the conversation.
 *
 * **`available` is read before anything else is believed.** The wire says
 * whether the media server answered at all, and a shelf read without asking
 * that is a shelf that reads as empty whenever the library is unreachable —
 * which is the one thing a screen must never draw, because it tells somebody
 * their collection is gone.
 *
 * **Nothing here composes an address.** A holding arrives as an identifier and
 * leaves as one. What it takes to play it is the core's to hand over, and a
 * reader turning an id and a server's name into a location would be this app
 * holding a second copy of how the library works — wrong the first time the
 * route to it is not the one it assumed.
 */
final readonly class Holdings
{
    /**
     * @param Envelope<mixed> $envelope the `held` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheyMayWatch
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ShelfIsUnreadable::missing(WireField::Data);
        }

        return self::reachable($data)
            ? WhatTheyMayWatch::told(self::shelf($data))
            : WhatTheyMayWatch::outOfReach(self::said($data));
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return HeldEnvelope::in($envelope)->data;
    }

    /**
     * Whether the media server answered for this shelf.
     *
     * Refused rather than defaulted when it is missing. The reassuring default
     * is *yes it did*, which turns every unreachable library into an empty one
     * silently, on the one payload shape that failed to say.
     *
     * @param array<array-key, mixed> $data
     */
    private static function reachable(array $data): bool
    {
        if (! array_key_exists(WireField::Available->value, $data)) {
            throw ShelfIsUnreadable::missing(WireField::Available);
        }

        $available = $data[WireField::Available->value];

        if (! is_bool($available)) {
            throw ShelfIsUnreadable::missing(WireField::Available);
        }

        return $available;
    }

    /**
     * What the core said about the listing itself.
     *
     * Carried in the core's words rather than rewritten: whoever could not
     * reach what is a fact about two machines, and a sentence composed here
     * would be this app guessing at which.
     *
     * @param array<array-key, mixed> $data
     */
    private static function said(array $data): Sentences
    {
        if (! array_key_exists(WireField::Findings->value, $data)) {
            throw ShelfIsUnreadable::missing(WireField::Findings);
        }

        $listed = $data[WireField::Findings->value];

        if (! is_array($listed)) {
            throw ShelfIsUnreadable::missing(WireField::Findings);
        }

        $said = [];

        foreach ($listed as $sentence) {
            if (! is_string($sentence)) {
                throw ShelfIsUnreadable::missing(WireField::Findings);
            }

            $said[] = Sentence::of($sentence);
        }

        return Sentences::of(...$said);
    }

    /**
     * Every holding the shelf listed, in the order it listed them.
     *
     * @param array<array-key, mixed> $data
     */
    private static function shelf(array $data): Shelf
    {
        if (! array_key_exists(WireField::Holdings->value, $data)) {
            throw ShelfIsUnreadable::missing(WireField::Holdings);
        }

        $listed = $data[WireField::Holdings->value];

        if (! is_array($listed)) {
            throw ShelfIsUnreadable::missing(WireField::Holdings);
        }

        $holdings = [];
        $position = 0;

        foreach ($listed as $said) {
            if (! is_array($said)) {
                throw ShelfIsUnreadable::holding($position);
            }

            $holdings[] = Holding::of(
                HoldingId::called(self::named($said, $position)),
                self::titled($said, $position),
                self::medium($said, $position),
                self::year($said, $position),
            );
            ++$position;
        }

        return Shelf::of(...$holdings);
    }

    /** @param array<array-key, mixed> $said */
    private static function named(array $said, int $position): string
    {
        if (! array_key_exists(WireField::Id->value, $said)) {
            throw ShelfIsUnreadable::holding($position);
        }

        $id = $said[WireField::Id->value];

        return is_string($id) ? $id : throw ShelfIsUnreadable::holding($position);
    }

    /** @param array<array-key, mixed> $said */
    private static function titled(array $said, int $position): string
    {
        if (! array_key_exists(WireField::Title->value, $said)) {
            throw ShelfIsUnreadable::holding($position);
        }

        $title = $said[WireField::Title->value];

        return is_string($title) ? $title : throw ShelfIsUnreadable::holding($position);
    }

    /**
     * What kind of thing one holding is.
     *
     * A medium this build has not heard of is refused by name rather than read
     * as {@see Medium::Other}. The two are different answers: one is a holding
     * the core had no better word for, and the other is a stack speaking a
     * vocabulary this release does not know.
     *
     * @param array<array-key, mixed> $said
     */
    private static function medium(array $said, int $position): Medium
    {
        if (! array_key_exists(WireField::Medium->value, $said)) {
            throw ShelfIsUnreadable::holding($position);
        }

        $medium = $said[WireField::Medium->value];

        if (! is_string($medium)) {
            throw ShelfIsUnreadable::holding($position);
        }

        return Medium::tryFrom($medium) ?? throw ShelfIsUnreadable::medium($medium);
    }

    /**
     * When it came out, where the core said.
     *
     * Absent and null are one answer here and neither is refused: the contract
     * carries the year optionally, so a holding nobody could date is ordinary
     * rather than malformed. Both arrive as unstated, which is a thing a screen
     * can draw — unlike a null, which is a thing a screen eventually prints.
     *
     * @param array<array-key, mixed> $said
     */
    private static function year(array $said, int $position): WhenItCameOut
    {
        if (! array_key_exists(WireField::Year->value, $said)) {
            return WhenItCameOut::unstated();
        }

        $year = $said[WireField::Year->value];

        if ($year === null) {
            return WhenItCameOut::unstated();
        }

        return is_int($year)
            ? WhenItCameOut::in($year)
            : throw ShelfIsUnreadable::holding($position);
    }
}
