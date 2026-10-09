<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One poster on a member's Home, flattened for a template: a title on the
 * shelf, or one of their own requests.
 *
 * Strings, keys and the step its name is lettered at, which is what a view
 * model is for: a template reaching into a value object is a template that
 * has to know the domain. The two lines a poster says — the one at the top of
 * its tile, and the label a screen reader hears — are catalogue keys chosen
 * here, filled with words as written and with words that are keys themselves,
 * a kind or a standing, which the poster translates before it fills them.
 *
 * **Nothing here is an address to the media server.** A poster that opens
 * its title carries the path of the title's screen in this app, which names
 * the title by the core's identifier inside the app's own route. A template
 * is handed the path whole and builds nothing out of it.
 */
final readonly class WhatOnePosterSays
{
    /** The line at the top of a dated title's tile: its year and its kind. */
    private const string ABOVE = 'household.poster.above';

    /** The line at the top of an undated title's tile: its kind alone. */
    private const string ABOVE_UNDATED = 'household.poster.above_undated';

    /** What a dated title's tile says to a screen reader. */
    private const string READS = 'household.poster.reads';

    /** What an undated title's tile says to a screen reader. */
    private const string READS_UNDATED = 'household.poster.reads_undated';

    /** What one of their own requests says to a screen reader: its name and where it stands. */
    private const string READS_STANDING = 'household.poster.reads_standing';

    /**
     * @param array<string, string> $filling what the two lines are filled with, as written
     * @param array<string, string> $keyed   what the two lines are filled with that is a catalogue key
     */
    private function __construct(
        public string $titled,
        public HowAPosterIsLettered $lettered,
        public string $above,
        public string $reads,
        public array $filling,
        public array $keyed,
        public string $goes,
        public string $plays,
    ) {}

    /**
     * A title on the shelf: its year and kind at the top, where the core
     * dated it, and its name, kind and year to a screen reader.
     *
     * @param string                $medium  the kind, as a catalogue key
     * @param string                $year    the year, or empty where the core could not date it
     * @param string $goes  the path its title opens at, or empty where it opens nothing
     * @param string $plays the title as the core names it, which Play asks for, or empty where it plays nothing
     */
    public static function ofATitle(string $titled, string $medium, string $year, string $goes = '', string $plays = ''): self
    {
        $isDated = $year !== '';

        return new self(
            titled: $titled,
            lettered: HowAPosterIsLettered::for($titled),
            above: $isDated ? self::ABOVE : self::ABOVE_UNDATED,
            reads: $isDated ? self::READS : self::READS_UNDATED,
            filling: ['title' => $titled, 'year' => $year],
            keyed: ['kind' => $medium],
            goes: $goes,
            plays: $plays,
        );
    }

    /**
     * One of their own requests: where it stands at the top, in their words,
     * and its name and standing to a screen reader. It opens nothing, since a
     * request carries nothing that names the title on the shelf.
     *
     * @param string $standing where it stands, as a catalogue key in the member's words
     */
    public static function ofARequest(string $titled, string $standing): self
    {
        return new self(
            titled: $titled,
            lettered: HowAPosterIsLettered::for($titled),
            above: $standing,
            reads: self::READS_STANDING,
            filling: ['title' => $titled],
            keyed: ['standing' => $standing],
            goes: '',
            plays: '',
        );
    }
}
