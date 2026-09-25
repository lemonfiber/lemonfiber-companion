<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A wanted part of a series that is not here yet, and the stage it rests at.
 *
 * The stage tells one that stalled apart from one still coming down.
 */
final readonly class AnEpisodeNotHereYet
{
    private function __construct(
        private int $season,
        private int $number,
        private string $title,
        private Stage $stage,
    ) {}

    /** One outstanding episode; a negative season or number, or a blank title, is refused. */
    public static function numbered(int $season, int $number, string $title, Stage $stage): self
    {
        if ($season < 0 || $number < 0) {
            throw TheTraceSaysNothing::about('number');
        }

        if (trim($title) === '') {
            throw TheTraceSaysNothing::about('title');
        }

        return new self($season, $number, $title, $stage);
    }

    public function season(): int
    {
        return $this->season;
    }

    public function number(): int
    {
        return $this->number;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function stage(): Stage
    {
        return $this->stage;
    }
}
