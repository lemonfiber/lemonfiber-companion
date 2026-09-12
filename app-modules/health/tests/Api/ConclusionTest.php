<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function array_filter;
use function array_map;
use function array_slice;
use function expect;
use function it;

use Modules\Health\Api\Conclusion;

use function sprintf;

it('reads the five the server sends', function (): void {
    // The values are the wire's. A rename here would make every finding
    // unparsable, and the parse is in an adapter where nothing else is looking
    // at these strings.
    expect(array_map(
        static fn(Conclusion $conclusion): string => $conclusion->value,
        Conclusion::cases(),
    ))->toBe(['fail', 'unverified', 'warn', 'pass', 'skipped']);
});

it('is declared worst first, which is what the ranking reads', function (): void {
    // The order in the file is the order on the screen. Pinned here because
    // nothing else would notice a case being moved: the enum would still
    // compile, every match would still be exhaustive, and the list would
    // quietly reorder itself.
    expect(Conclusion::cases())->toBe([
        Conclusion::Failed,
        Conclusion::Unverified,
        Conclusion::Warned,
        Conclusion::Passed,
        Conclusion::Skipped,
    ]);
});

it('puts not knowing above a warning', function (): void {
    // The distinction the whole subsystem turns on. Not knowing whether the
    // tunnel leaks is worse than knowing the disk is filling, and a check that
    // could not run must never read as one that passed.
    expect(Conclusion::Unverified->isWorseThan(Conclusion::Warned))->toBeTrue()
        ->and(Conclusion::Unverified->isWorseThan(Conclusion::Passed))->toBeTrue()
        ->and(Conclusion::Warned->isWorseThan(Conclusion::Unverified))->toBeFalse();
});

it('is not worse than itself', function (): void {
    $notWorse = array_filter(
        Conclusion::cases(),
        static fn(Conclusion $conclusion): bool => $conclusion->isWorseThan($conclusion),
    );

    expect($notWorse)->toBe([]);
});

it('ranks every pair one way or the other, and never both', function (): void {
    // Over all twenty-five pairs, so that a case added tomorrow is covered on
    // the day. Two cases that were each worse than the other would make the
    // sort order depend on which was compared first.
    $contradictions = [];

    foreach (Conclusion::cases() as $one) {
        foreach (Conclusion::cases() as $other) {
            if ($one->isWorseThan($other) && $other->isWorseThan($one)) {
                $contradictions[] = sprintf('%s and %s are each worse', $one->value, $other->value);
            }
        }
    }

    expect($contradictions)->toBe([]);
});

it('agrees with the order it is declared in', function (): void {
    // The property the ranking is built on, asserted rather than assumed:
    // anything earlier in `cases()` is worse than anything after it.
    $wrong = [];
    $cases = Conclusion::cases();

    foreach ($cases as $at => $one) {
        foreach (array_slice($cases, $at + 1) as $later) {
            if (! $one->isWorseThan($later)) {
                $wrong[] = sprintf('%s is declared before %s and is not worse', $one->value, $later->value);
            }
        }
    }

    expect($wrong)->toBe([]);
});
