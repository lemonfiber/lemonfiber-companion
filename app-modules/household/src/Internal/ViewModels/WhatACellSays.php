<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One stretch of a channel's row in the guide: a programme, or the empty space where the core listed none.
 *
 * A gap carries no title, no times and no tap: the core leaves a programme
 * outside the member's limits out, and nothing here says one was there.
 */
final readonly class WhatACellSays
{
    /**
     * @param string $title       the programme's title, or empty for a gap
     * @param string $times       when it starts and ends, as a clock reads them, or empty for a gap
     * @param string $certificate its certificate, or empty for a gap or where it carries none
     * @param int    $width       how wide it is drawn, in points
     * @param string $tap         what pressing it does, or empty for a gap
     * @param bool   $isOnNow     whether it is on now, which pressing tunes to
     * @param int    $at          where it sits in its channel's programmes, as the core sent them, or -1 for a gap
     */
    private function __construct(
        public string $title,
        public string $times,
        public string $certificate,
        public int $width,
        public string $tap,
        public bool $isOnNow,
        public int $at,
    ) {}

    public static function aGap(int $width): self
    {
        return new self(title: '', times: '', certificate: '', width: $width, tap: '', isOnNow: false, at: -1);
    }

    public static function aProgramme(string $title, string $times, string $certificate, int $width, string $tap, bool $isOnNow, int $at): self
    {
        return new self($title, $times, $certificate, $width, $tap, $isOnNow, $at);
    }

    public function isAGap(): bool
    {
        return $this->tap === '';
    }
}
