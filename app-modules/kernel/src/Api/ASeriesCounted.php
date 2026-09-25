<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A traced series counted across its seasons: what was asked for, what is here, and what nobody asked for.
 *
 * What nobody asked for is counted apart rather than folded into what was
 * wanted.
 */
final readonly class ASeriesCounted
{
    private function __construct(
        private int $have,
        private int $wanted,
        private int $unmonitored,
        private TheSeasonsOfIt $seasons,
    ) {}

    /** The counts and each season; a negative count, or more here than wanted, is refused. */
    public static function counted(int $have, int $wanted, int $unmonitored, HowMuchOfASeasonIsHere ...$seasons): self
    {
        if ($have < 0 || $unmonitored < 0 || $have > $wanted) {
            throw TheTraceSaysNothing::about('coverage');
        }

        return new self($have, $wanted, $unmonitored, TheSeasonsOfIt::of(...$seasons));
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

    public function seasons(): TheSeasonsOfIt
    {
        return $this->seasons;
    }
}
