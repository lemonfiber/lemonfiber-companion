<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function array_key_last;
use function array_last;
use function array_map;
use function array_pop;
use function count;
use function max;
use function min;

use Modules\Design\Api\ATap;
use Modules\Design\Api\TheSmallestTarget;
use Modules\Household\Internal\ViewModels\WhatACellSays;

use function sprintf;

/**
 * A channel's row cut so every programme a finger presses is at least as wide as a finger needs, with every stretch still starting where its time does.
 *
 * Whole programmes too short to press, one after another, are drawn as one
 * cell across the time they run, which lists them. Where they run for less
 * than a finger needs, the cell takes the empty time after them, then the
 * empty time before them, then the programmes after them, and past the row's
 * end where nothing follows. A programme the grid's start or the programme
 * before it cut that short is drawn as an edge, which is not pressed.
 */
final class ARowBeingCut
{
    /** @var array<int, int> how wide each piece is still drawn, as empty time is taken by the cells beside it */
    private array $widths;

    /** @var list<WhatACellSays> */
    private array $cells = [];

    private int $next = 0;

    /** @param list<APieceOfARow> $pieces each stretch of the row, left to right */
    public function __construct(private readonly int $row, private readonly array $pieces)
    {
        $this->widths = array_map(static fn(APieceOfARow $piece): int => $piece->cell->width, $pieces);
    }

    /** @return list<WhatACellSays> */
    public function cut(): array
    {
        while ($this->next < count($this->pieces)) {
            $piece = $this->pieces[$this->next];

            if ($piece->isShortAndWhole()) {
                $this->gather();

                continue;
            }

            $this->drawn($piece);
        }

        return $this->cells;
    }

    private function drawn(APieceOfARow $piece): void
    {
        $width = $this->widths[$this->next];
        $this->next++;

        if ($piece->isEmpty()) {
            if ($width > 0) {
                $this->cells[] = WhatACellSays::aGap($width);
            }

            return;
        }

        $this->cells[] = $piece->isAnEdge() ? $piece->cell->cutOff() : $piece->cell;
    }

    /** Draw the short programmes from here on as one cell a finger can press. */
    private function gather(): void
    {
        $gathered = [];
        $width = 0;

        while ($this->next < count($this->pieces) && $this->pieces[$this->next]->isShortAndWhole()) {
            $gathered[] = $this->pieces[$this->next];
            $width += $this->widths[$this->next];
            $this->next++;
        }

        $width = $this->takingFromTheEmptyBefore($this->takingFromTheEmptyAfter($width));

        while ($width < TheSmallestTarget::POINTS && $this->next < count($this->pieces)) {
            if ($this->pieces[$this->next]->isEmpty()) {
                $width = $this->takingFromTheEmptyAfter($width);

                continue;
            }

            $gathered[] = $this->pieces[$this->next];
            $width += $this->widths[$this->next];
            $this->next++;
        }

        $this->cells[] = $this->together(max($width, TheSmallestTarget::POINTS), ...$gathered);
    }

    private function takingFromTheEmptyAfter(int $width): int
    {
        if ($width >= TheSmallestTarget::POINTS || $this->next >= count($this->pieces) || ! $this->pieces[$this->next]->isEmpty()) {
            return $width;
        }

        $taken = min(TheSmallestTarget::POINTS - $width, $this->widths[$this->next]);
        $this->widths[$this->next] -= $taken;

        if ($this->widths[$this->next] === 0) {
            $this->next++;
        }

        return $width + $taken;
    }

    private function takingFromTheEmptyBefore(int $width): int
    {
        $last = array_key_last($this->cells);

        if ($width >= TheSmallestTarget::POINTS || $last === null || ! $this->cells[$last]->isAGap()) {
            return $width;
        }

        $empty = $this->cells[$last]->width;
        $taken = min(TheSmallestTarget::POINTS - $width, $empty);
        array_pop($this->cells);

        if ($taken < $empty) {
            $this->cells[] = WhatACellSays::aGap($empty - $taken);
        }

        return $width + $taken;
    }

    private function together(int $width, APieceOfARow $first, APieceOfARow ...$after): WhatACellSays
    {
        if ($after === []) {
            return $first->cell->drawnAt($width);
        }

        $last = array_last($after);

        return WhatACellSays::together(
            sprintf(HowTheGuideReads::FROM_TO, $first->starts, $last->ends),
            $width,
            ATap::calling(HowTheGuideReads::LISTS, $this->row, count($this->cells))->written(),
            $first->cell,
            ...array_map(static fn(APieceOfARow $piece): WhatACellSays => $piece->cell, $after),
        );
    }
}
