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

    /** What something they were part-way through says to a screen reader. */
    private const string READS_PART_WAY = 'household.poster.reads_part_way';

    /** What one of their own requests says to a screen reader: its name and where it stands. */
    private const string READS_STANDING = 'household.poster.reads_standing';

    /**
     * @param array<string, string> $filling what the two lines are filled with, as written
     * @param array<string, string> $keyed   what the two lines are filled with that is a catalogue key
     * @param string|null           $goes     the path its title opens at, or null where it opens nothing
     * @param string|null           $plays    the title as the core names it, which Play asks for, or null where it plays nothing
     * @param string|null           $tap      what pressing it does on the screen drawing it, or null where pressing it does nothing of its own
     * @param string|null           $pictured the source its picture is drawn from over its lettering, or null where it is only lettered
     */
    private function __construct(
        public string $titled,
        public HowAPosterIsLettered $lettered,
        public string $above,
        public string $reads,
        public array $filling,
        public array $keyed,
        public ?string $goes = null,
        public ?string $plays = null,
        public ?string $tap = null,
        public ?string $pictured = null,
    ) {}

    /** The same poster, drawn from this source over its lettering, or lettered alone where there is none. */
    public function pictured(?string $source): self
    {
        return new self($this->titled, $this->lettered, $this->above, $this->reads, $this->filling, $this->keyed, $this->goes, $this->plays, $this->tap, $source);
    }

    /**
     * A title on the shelf: its year and kind at the top, where the core
     * dated it, and its name, kind and year to a screen reader.
     *
     * @param string      $medium the kind, as a catalogue key
     * @param string|null $year   the year, or null where the core could not date it
     * @param string|null $goes   the path its title opens at, or null where it opens nothing
     * @param string|null $plays  the title as the core names it, which Play asks for, or null where it plays nothing
     */
    public static function ofATitle(string $titled, string $medium, ?string $year, ?string $goes = null, ?string $plays = null): self
    {
        if ($year === null) {
            return new self($titled, HowAPosterIsLettered::for($titled), self::ABOVE_UNDATED, self::READS_UNDATED, ['title' => $titled], ['kind' => $medium], $goes, $plays);
        }

        return new self($titled, HowAPosterIsLettered::for($titled), self::ABOVE, self::READS, ['title' => $titled, 'year' => $year], ['kind' => $medium], $goes, $plays);
    }

    /**
     * Something they were part-way through: how long is left at the top, and
     * its name and that they are part-way through it to a screen reader.
     * Pressing it plays it from where they left off.
     *
     * @param string                $above   the line at the top, as a catalogue key
     * @param array<string, string> $filling what that line is filled with, as written
     * @param string                $tap     what pressing it does on the screen drawing it
     */
    public static function ofSomethingPartWay(string $titled, string $above, array $filling, string $tap): self
    {
        return new self(
            titled: $titled,
            lettered: HowAPosterIsLettered::for($titled),
            above: $above,
            reads: self::READS_PART_WAY,
            filling: ['title' => $titled, ...$filling],
            keyed: [],
            tap: $tap,
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
        );
    }

    /**
     * One of their own requests nothing has named yet: lettered with the kind of thing it is, in their words.
     *
     * @param string $kind     what it is, as a catalogue key in the member's words
     * @param string $standing where it stands, as a catalogue key in the member's words
     */
    public static function ofARequestNotYetNamed(string $kind, string $standing): self
    {
        return new self(
            titled: $kind,
            lettered: HowAPosterIsLettered::Small,
            above: $standing,
            reads: self::READS_STANDING,
            filling: [],
            keyed: ['title' => $kind, 'standing' => $standing],
        );
    }
}
