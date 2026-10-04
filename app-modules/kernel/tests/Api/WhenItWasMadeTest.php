<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhenItWasMade;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** The moment as epoch seconds, or the word for an unreadable clock. Named for this file (`G10`). */
function whatTheClockSays(WhenItWasMade $when): string
{
    return $when->either(
        at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d', $at->epochSeconds())),
        unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unreadable'),
    )->said;
}

/** A known moment, by its epoch seconds. */
function aMomentAt(int $seconds): WhenItWasMade
{
    return WhenItWasMade::at(Instant::atEpochSeconds($seconds));
}

it('carries the moment a change was made', function (): void {
    expect(whatTheClockSays(aMomentAt(1_790_142_840)))->toBe('1790142840');
});

it('carries that the clock would not say, as its own arm rather than as 1970', function (): void {
    expect(whatTheClockSays(WhenItWasMade::unreadable()))->toBe('unreadable');
});

it('two changes at one known moment are the same moment', function (): void {
    expect(aMomentAt(1_790_142_840)->isTheSameMomentAs(aMomentAt(1_790_142_840)))->toBeTrue();
});

it('two known moments a second apart are not', function (): void {
    expect(aMomentAt(1_790_142_840)->isTheSameMomentAs(aMomentAt(1_790_142_841)))->toBeFalse();
});

it('an unreadable clock is never the same moment as anything', function (): void {
    // Not as another unreadable clock — two changes nothing dated are two
    // changes nothing orders, and drawing them as one moment would claim they
    // happened together. Not as a known moment either, from either side.
    $unknown = WhenItWasMade::unreadable();

    expect($unknown->isTheSameMomentAs(WhenItWasMade::unreadable()))->toBeFalse()
        ->and($unknown->isTheSameMomentAs(aMomentAt(1_790_142_840)))->toBeFalse()
        ->and(aMomentAt(1_790_142_840)->isTheSameMomentAs($unknown))->toBeFalse();
});
