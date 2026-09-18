<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function round;

/**
 * A size as it reaches a screen: a figure, and the unit it is counted in.
 *
 * A size is shown before a request is approved, and *how many bytes*
 * has to become words somewhere. This is that somewhere, and the two halves are
 * one value because they are useless apart — a figure without its unit is a
 * number meaning nothing, and a screen holding them separately is a screen that
 * can render one of them stale.
 *
 * **One static named constructor, which is also what `D2` allows.** A primitive
 * crosses into a module in exactly one place, where it is checked and given a
 * name; {@see SizeUnit} takes no numbers at all and answers only about itself.
 *
 * **The unit is the smallest one whose figure stays under a thousand.** That
 * is `L5` as a rule rather than as an aspiration: a separator written into a
 * source file is wrong in one locale by construction, so no figure that leaves
 * here may need one. Walking up rather than picking a band and correcting it
 * afterwards means there is one decision and one boundary.
 *
 * **It does not overstate.** 600 MB stays `600 MB` rather than becoming `1 GB`,
 * because the walk only moves up when staying would need four figures — and an
 * operator deciding whether something fits is the last person who should be
 * told a thing is bigger than it is.
 *
 * **The largest band has no ceiling, because there is nothing above it.** A
 * size past a thousand terabytes is shown with four figures and a separator
 * this file cannot get right. That is stated rather than hidden: it is nine
 * petabytes of television, which is not a request a household makes, and the
 * alternative is a fourth unit nobody would recognise on a phone. Every band
 * that *has* one above it is guaranteed, and the test derives its cases from
 * exactly that set so a fourth unit could not arrive without one.
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

    /**
     * The one place a number of bytes becomes something a person reads.
     *
     * **One mechanism, walked upwards.** Start in the smallest unit and move up
     * while the figure would reach a thousand. A size below a megabyte says
     * nought of them, which is honest for a request that really is that small
     * and is the only band where that can happen — a stack does not offer to
     * fetch nothing.
     *
     * This replaced a band chosen by `$bytes >= $unit->bytes()` with the step-up
     * kept as a separate escape, and the pair turned out to be one decision
     * spelled twice: at exactly a boundary the comparison picked the upper unit
     * directly, and without it the lower unit's figure reached a thousand and
     * the escape arrived at the same answer by the other road. Mutation testing
     * found it — the `>=` was unobservable, over two hundred thousand inputs,
     * because the escape subsumed it. Two mechanisms for one situation is the
     * shape {@see SizeUnit} was introduced to remove, and it had grown back
     * here.
     */
    public static function of(int $bytes): self
    {
        $unit = SizeUnit::Megabytes;

        while (self::inside($bytes, $unit) >= self::A_THOUSAND && $unit->next() !== $unit) {
            $unit = $unit->next();
        }

        return new self(self::inside($bytes, $unit), $unit->saidOnTheScreen());
    }

    /**
     * How many of a unit a number of bytes comes to, to the nearest whole.
     *
     * Rounded rather than floored, because flooring understates: 1.9 TB read as
     * `1 TB` is a ninety-per-cent understatement of the one number somebody is
     * deciding from. `L5` rules out the decimal place that would settle it
     * either way — a separator written into a source file is wrong in one
     * locale by construction — and the precision lost is precision the number
     * did not have, since most of these are estimates.
     */
    private static function inside(int $bytes, SizeUnit $unit): int
    {
        return (int) round($bytes / $unit->bytes());
    }
}
