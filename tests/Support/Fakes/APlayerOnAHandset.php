<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\WhatOpeningCameTo;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * A handset's player, remembering what it was handed and answering where it stands from a list.
 *
 * `working()` opens whatever it is handed; `refusing()` opens nothing, for one
 * reason. Where it stands is answered from {@see standing()}'s list in turn,
 * the last again once the list runs out, and is closed until it is given one.
 */
final class APlayerOnAHandset implements Playing
{
    /** @var list<ATitleToPlay> */
    private array $opened = [];

    /** @var list<WherePlayingStands> */
    private array $standing;

    private int $closed = 0;

    private function __construct(private readonly ?WhyPlayingDidNotStart $why)
    {
        $this->standing = [WherePlayingStands::of(PlaybackIs::Closed, HowFarIn::theStart())];
    }

    public static function working(): self
    {
        return new self(null);
    }

    public static function refusing(WhyPlayingDidNotStart $why): self
    {
        return new self($why);
    }

    /** Answer where it stands with these, in turn. */
    public function standing(WherePlayingStands $first, WherePlayingStands ...$then): self
    {
        $this->standing = array_values([$first, ...$then]);

        return $this;
    }

    public function open(ATitleToPlay $title): WhatOpeningCameTo
    {
        if ($this->why instanceof WhyPlayingDidNotStart) {
            return WhatOpeningCameTo::refused($this->why);
        }

        $this->opened[] = $title;

        return WhatOpeningCameTo::opened();
    }

    public function whereItStands(): WherePlayingStands
    {
        return count($this->standing) > 1 ? array_shift($this->standing) : $this->standing[0];
    }

    public function close(): WherePlayingStands
    {
        $this->closed++;

        return WherePlayingStands::of(PlaybackIs::Closed, HowFarIn::theStart());
    }

    /**
     * Every title it put on screen, in order.
     *
     * @return list<ATitleToPlay>
     */
    public function opened(): array
    {
        return $this->opened;
    }

    /** How many times it was taken off screen. */
    public function timesClosed(): int
    {
        return $this->closed;
    }
}
