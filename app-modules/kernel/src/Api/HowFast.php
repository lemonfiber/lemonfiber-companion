<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function round;

/**
 * A line's speed as it reaches a screen: a figure, and the unit it is counted in.
 *
 * {@see HowBig}'s rule, for a rate rather than a size: one named constructor
 * where the number crosses in, the smallest unit whose figure stays under a
 * thousand, rounded rather than floored, and never overstated by choosing the
 * band off a rounded figure. It takes bytes a second, which is what the
 * contract carries, and says bits a second, which is what a line is sold in —
 * the conversion made here and once, so no template multiplies by eight.
 */
final readonly class HowFast
{
    /** What a figure may not reach, which is what keeps a separator out of it. */
    private const int A_THOUSAND = 1000;

    /** Bits in a byte, which is the whole of the conversion. */
    private const int BITS_IN_A_BYTE = 8;

    /**
     * @param int    $figure how many of the unit, whole and under a thousand
     * @param string $said   the catalogue key for what the figure counts
     */
    private function __construct(public int $figure, public string $said) {}

    /**
     * How fast a line carries, from the bytes a second the contract says.
     *
     * Not checked for a negative rate here: {@see WhatTheLineCarries} refuses
     * one, and a second copy of that rule would be one no answer could reach.
     */
    public static function of(int $bytesASecond): self
    {
        $bits = $bytesASecond * self::BITS_IN_A_BYTE;
        $unit = RateUnit::Kilobits;

        while (self::inside($bits, $unit) >= self::A_THOUSAND && $unit->next() !== $unit) {
            $unit = $unit->next();
        }

        return new self(self::inside($bits, $unit), $unit->saidOnTheScreen());
    }

    private static function inside(int $bits, RateUnit $unit): int
    {
        return (int) round($bits / $unit->bits());
    }
}
