<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How much of one season is here, and what is outstanding.
 *
 * Wanted counts the parts somebody asked for or that are here already; parts
 * nobody asked for are counted apart rather than inflating it, so a season
 * with every wanted episode present reads as complete even where specials are
 * not. Season zero is where a service files specials.
 */
final readonly class HowMuchOfASeasonIsHere
{
    private function __construct(
        private int $season,
        private int $have,
        private int $wanted,
        private int $unmonitored,
        private TheEpisodesNotHereYet $outstanding,
    ) {}

    /**
     * One season's counts and its outstanding episodes.
     *
     * A negative count, or more here than wanted, is refused: either is a
     * season that cannot be.
     */
    public static function counted(int $season, int $have, int $wanted, int $unmonitored, AnEpisodeNotHereYet ...$outstanding): self
    {
        if ($season < 0 || $have < 0 || $unmonitored < 0 || $have > $wanted) {
            throw TheTraceSaysNothing::about('season');
        }

        return new self($season, $have, $wanted, $unmonitored, TheEpisodesNotHereYet::of(...$outstanding));
    }

    public function season(): int
    {
        return $this->season;
    }

    public function have(): int
    {
        return $this->have;
    }

    public function wanted(): int
    {
        return $this->wanted;
    }

    public function unmonitored(): int
    {
        return $this->unmonitored;
    }

    /** The wanted parts not here yet, each with the stage it rests at. */
    public function outstanding(): TheEpisodesNotHereYet
    {
        return $this->outstanding;
    }
}
