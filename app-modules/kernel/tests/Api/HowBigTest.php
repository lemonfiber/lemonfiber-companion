<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\SizeUnit;

use function sprintf;

/** A size as it reaches a screen: the figure and the unit it is said in (`G10`). */
function asShown(int $bytes): string
{
    $shown = HowBig::of($bytes);

    return sprintf('%d %s', $shown->figure, $shown->said);
}

/**
 * Every band that has one above it, derived rather than listed.
 *
 * The largest band cannot round out of itself — there is nothing above it to
 * round into — so it is the one case the rule below does not apply to. Derived
 * so that a fourth unit added later cannot arrive without its case.
 *
 * @return list<SizeUnit>
 */
function everyBandWithOneAbove(): array
{
    return array_values(array_filter(
        SizeUnit::cases(),
        static fn(SizeUnit $unit): bool => $unit->next() !== $unit,
    ));
}

it('D7-R3 — says a size in the largest unit it fills', function (): void {
    expect(asShown(4_000_000_000))->toBe('4 household.gigabytes')
        ->and(asShown(900_000_000))->toBe('900 household.megabytes')
        ->and(asShown(2_000_000_000_000))->toBe('2 household.terabytes');
});

it('chooses the band by the raw figure, so nothing is overstated', function (): void {
    // *Largest unit whose rounded figure is at least one* would read this as
    // `1 gigabytes`, overstating a download by two thirds. An operator deciding
    // whether something fits is the last person who should be told a thing is
    // bigger than it is.
    expect(asShown(600_000_000))->toBe('600 household.megabytes');
});

it('rounds rather than floors, because flooring understates', function (): void {
    // 1.9 TB read as `1 terabytes` is a ninety-per-cent understatement of the
    // one number somebody is deciding from.
    expect(asShown(1_900_000_000_000))->toBe('2 household.terabytes')
        ->and(asShown(1_400_000_000))->toBe('1 household.gigabytes');
});

it('L5 — no figure reaches a thousand, in any band that has one above it', function (): void {
    // The defect this escape exists for. A size of 999.6 GB is below a
    // terabyte, so the band is gigabytes — and rounds to 1000, a four-figure
    // number in a band that was supposed to make one impossible. Every band
    // below the largest can do it, and the window is half a per cent wide at
    // the top of each, which is narrow enough to survive a long time and
    // certain to be met eventually.
    foreach (everyBandWithOneAbove() as $unit) {
        $justUnder = (int) ($unit->bytes() * 999.6);
        $shown = HowBig::of($justUnder);

        expect($shown->figure)->toBe(1, sprintf('%s rounding out of its band', $unit->value))
            ->and($shown->said)->toBe($unit->next()->saidOnTheScreen(), $unit->value);
    }
});

it('L5 — the figure below that window stays in its own band', function (): void {
    // The other side of the same edge, so the escape cannot be a rule that
    // fires everywhere: 999.4 of a unit is still 999 of it.
    foreach (everyBandWithOneAbove() as $unit) {
        $under = (int) ($unit->bytes() * 999.4);
        $shown = HowBig::of($under);

        expect($shown->said)->toBe($unit->saidOnTheScreen(), sprintf('%s should stay put', $unit->value))
            ->and($shown->figure)->toBe(999, $unit->value);
    }
});

it('says a size smaller than any unit in the smallest one', function (): void {
    // Not an error and not nothing: a request really can be that small, and
    // megabytes with a figure of nought is what the catalogue says for it.
    expect(asShown(0))->toBe('0 household.megabytes')
        ->and(asShown(1))->toBe('0 household.megabytes');
});

it('L7 — every unit names a line, built from the case', function (): void {
    foreach (SizeUnit::cases() as $unit) {
        expect($unit->saidOnTheScreen())
            ->toBe(sprintf('household.%s', $unit->value), $unit->name);
    }
});

it('the largest band answers itself when asked what is above it', function (): void {
    // Which is what `everyBandWithOneAbove()` reads to exclude it, and what
    // keeps the escape from walking off the end.
    $largest = SizeUnit::Terabytes;

    expect($largest->next())->toBe($largest);
});
