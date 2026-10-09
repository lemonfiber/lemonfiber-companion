<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a member chose to hear and to read while a title plays.
 *
 * A setting of the phone in their hand, not of the household: the house is
 * never told it. A title with no sound in the language chosen plays its own,
 * and one with no subtitles in it plays with none; the player decides that
 * from the tracks it finds, and nothing here refuses a title for it.
 */
final readonly class TheirLanguages
{
    private function __construct(
        private HearIn $hear,
        private ReadIn $read,
    ) {}

    public static function of(HearIn $hear, ReadIn $read): self
    {
        return new self($hear, $read);
    }

    /** What a member who chose nothing gets: the title's own sound, and no subtitles. */
    public static function asTheTitleComes(): self
    {
        return new self(HearIn::TheOriginal, ReadIn::Nothing);
    }

    public function hear(): HearIn
    {
        return $this->hear;
    }

    public function read(): ReadIn
    {
        return $this->read;
    }

    /** The same choice, heard in this language instead. */
    public function hearing(HearIn $hear): self
    {
        return new self($hear, $this->read);
    }

    /** The same choice, read in this language instead. */
    public function reading(ReadIn $read): self
    {
        return new self($this->hear, $read);
    }
}
