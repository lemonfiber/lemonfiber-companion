<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function array_values;

/**
 * One stretch of a channel's row in the guide: a programme, the cut-off edge of one, several short ones together, or the empty space where the core listed none.
 *
 * A gap carries no title, no times and no tap: the core leaves a programme
 * outside the member's limits out, and nothing here says one was there.
 *
 * An edge is a programme cut off by the start of the grid or by the programme
 * before it, and left too narrow to press: it is drawn and carries no tap. Several
 * programmes too short to press one by one are drawn as one cell across the
 * time they run, which opens a list of them.
 */
final readonly class WhatACellSays
{
    /**
     * @param string             $title       the programme's title, or empty for a gap or several together
     * @param string             $times       when it starts and ends, as a clock reads them, from the first's start to the last's end for several together, or empty for a gap
     * @param string             $certificate its certificate, or empty for a gap, several together, or where it carries none
     * @param int                $width       how wide it is drawn, in points
     * @param string             $tap         what pressing it does, or empty for a gap or an edge
     * @param bool               $isOnNow     whether it is on now, or holds the programme on now
     * @param int                $at          where it sits in its channel's programmes, as the core sent them, or -1 for a gap or several together
     * @param bool               $isAnEdge    whether it is the cut-off edge of a programme, drawn and not pressed
     * @param list<WhatACellSays> $together    each programme it holds, in the order they run, or none where it is not several together
     */
    private function __construct(
        public string $title,
        public string $times,
        public string $certificate,
        public int $width,
        public string $tap,
        public bool $isOnNow,
        public int $at,
        public bool $isAnEdge,
        public array $together,
    ) {}

    public static function aGap(int $width): self
    {
        return new self(title: '', times: '', certificate: '', width: $width, tap: '', isOnNow: false, at: -1, isAnEdge: false, together: []);
    }

    public static function aProgramme(string $title, string $times, string $certificate, int $width, string $tap, bool $isOnNow, int $at): self
    {
        return new self($title, $times, $certificate, $width, $tap, $isOnNow, $at, isAnEdge: false, together: []);
    }

    /** Several programmes as one cell across the time they run, from the first's start to the last's end, which pressing lists. */
    public static function together(string $times, int $width, string $tap, self ...$programmes): self
    {
        $isOnNow = false;

        foreach ($programmes as $programme) {
            $isOnNow = $isOnNow || $programme->isOnNow;
        }

        return new self(title: '', times: $times, certificate: '', width: $width, tap: $tap, isOnNow: $isOnNow, at: -1, isAnEdge: false, together: array_values($programmes));
    }

    /** The same programme as an edge: drawn as wide as it is, and not pressed. */
    public function cutOff(): self
    {
        return new self($this->title, $this->times, $this->certificate, $this->width, tap: '', isOnNow: $this->isOnNow, at: $this->at, isAnEdge: true, together: []);
    }

    /** The same stretch, drawn this wide. */
    public function drawnAt(int $width): self
    {
        return new self($this->title, $this->times, $this->certificate, $width, $this->tap, $this->isOnNow, $this->at, $this->isAnEdge, $this->together);
    }

    public function isAGap(): bool
    {
        return $this->tap === '' && ! $this->isAnEdge;
    }

    /** Whether it holds several programmes, which pressing lists. */
    public function isSeveral(): bool
    {
        return $this->together !== [];
    }
}
