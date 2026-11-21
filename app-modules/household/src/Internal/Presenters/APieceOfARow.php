<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Design\Api\TheSmallestTarget;
use Modules\Household\Internal\ViewModels\WhatACellSays;

/**
 * One stretch of a channel's row before it is cut for a finger: a programme as wide as it runs, or empty time.
 */
final readonly class APieceOfARow
{
    private function __construct(public WhatACellSays $cell, public bool $isCut, public string $starts, public string $ends) {}

    /** The time between two programmes, where the core listed none. */
    public static function empty(int $width): self
    {
        return new self(WhatACellSays::aGap($width), isCut: false, starts: '', ends: '');
    }

    /** A programme, whether the grid's start or the one before it cut its start off, and when it starts and ends on the clock. */
    public static function of(WhatACellSays $programme, bool $isCut, string $starts, string $ends): self
    {
        return new self($programme, $isCut, $starts, $ends);
    }

    public function isEmpty(): bool
    {
        return $this->cell->isAGap();
    }

    /** Whether it is a whole programme drawn narrower than a finger needs. */
    public function isShortAndWhole(): bool
    {
        return ! $this->isEmpty() && ! $this->isCut && $this->cell->width < TheSmallestTarget::POINTS;
    }

    /** Whether it is a programme cut so narrow a finger cannot press it. */
    public function isAnEdge(): bool
    {
        return $this->isCut && $this->cell->width < TheSmallestTarget::POINTS;
    }
}
