<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function array_reverse;
use function round;

/**
 * A size as it reaches a screen: a figure, and the unit it is counted in.
 *
 * `D7-R3` wants a size shown before a request is approved, and *how many bytes*
 * has to become words somewhere. This is that somewhere, and the two halves are
 * one value because they are useless apart — a figure without its unit is a
 * number meaning nothing, and a screen holding them separately is a screen that
 * can render one of them stale.
 *
 * **One static named constructor, which is also what `D2` allows.** A primitive
 * crosses into a module in exactly one place, where it is checked and given a
 * name; {@see SizeUnit} takes no numbers at all and answers only about itself.
 *
 * **The band is chosen by the raw figure rather than by the rounded one**, and
 * that is the half worth defending: *largest unit whose rounded figure is at
 * least one* reads 600 MB as `1 GB`, which overstates a download by two thirds.
 * An operator deciding whether something fits is the last person who should be
 * told a thing is bigger than it is.
 *
 * **Then one escape, because rounding can leave the band it was chosen in.** A
 * size of 999.6 GB is below a terabyte, so the band is gigabytes — and rounds
 * to `1000 GB`, a four-figure number in a band that was supposed to make one
 * impossible, and one carrying a separator `L5` refuses to write. Where that
 * happens the unit above is the answer and the figure there is exactly one.
 * Every band below the largest can do it, and the window is half a per cent
 * wide at the top of each: narrow enough to survive a long time, and certain to
 * be met eventually.
 *
 * The escape lives beside the rounding rather than in {@see SizeUnit}, so the
 * band and the figure are decided together. Split across two calls they are two
 * decisions that have to agree, which is the shape this type exists to remove.
 */
final readonly class HowBig
{
    /** What a figure may not reach, which is what keeps a separator out of it. */
    private const int A_THOUSAND = 1000;

    /**
     * @param int    $figure how many of the unit, whole and under a thousand
     * @param string $said   the catalogue key for what the figure counts
     */
    private function __construct(public int $figure, public string $said) {}

    /** The one place a number of bytes becomes something a person reads. */
    public static function of(int $bytes): self
    {
        $filled = array_find(
            array_reverse(SizeUnit::cases()),
            static fn(SizeUnit $unit): bool => $bytes >= $unit->bytes(),
        ) ?? SizeUnit::Megabytes;

        $figure = self::inside($bytes, $filled);

        return $figure < self::A_THOUSAND
            ? new self($figure, $filled->saidOnTheScreen())
            : new self(self::inside($bytes, $filled->next()), $filled->next()->saidOnTheScreen());
    }

    /**
     * How many of a unit a number of bytes comes to, to the nearest whole.
     *
     * Rounded rather than floored, because flooring understates: 1.9 TB read as
     * `1 TB` is a ninety-per-cent understatement of the one number somebody is
     * deciding from. `L5` rules out the decimal place that would settle it
     * either way — a separator written into a source file is wrong in one
     * locale by construction — and the precision lost is precision the number
     * did not have, since `D7-R4` exists because most of these are estimates.
     */
    private static function inside(int $bytes, SizeUnit $unit): int
    {
        return (int) round($bytes / $unit->bytes());
    }
}
