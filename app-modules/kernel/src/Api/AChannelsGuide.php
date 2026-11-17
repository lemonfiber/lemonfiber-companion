<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One channel in the guide: its name, its number where it has one, its programmes across the window, and where its guide ends.
 *
 * The programmes are the core's, in the order it sent them, with each one
 * outside the member's limits left out; nothing here fills, widens or
 * narrows them.
 */
final readonly class AChannelsGuide
{
    private function __construct(private ChannelId $id, private string $name, private string $number, private TheAirings $airings, private WhenItSays $until) {}

    public static function of(ChannelId $id, string $name, string $number, WhenItSays $until, AnAiring ...$airings): self
    {
        return new self($id, $name, $number, TheAirings::of(...$airings), $until);
    }

    public function id(): ChannelId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Its number on the tuner, or empty where it has none. */
    public function number(): string
    {
        return $this->number;
    }

    public function airings(): TheAirings
    {
        return $this->airings;
    }

    /** Where the media server's guide ends on it inside the window, or unsaid where it holds none. */
    public function until(): WhenItSays
    {
        return $this->until;
    }
}
