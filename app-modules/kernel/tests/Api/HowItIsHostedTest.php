<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_unique;
use function expect;
use function it;

use Modules\Kernel\Api\HowItIsHosted;

use function sprintf;

/**
 * Every standing that reads as *installed and not running*, as a list.
 *
 * Named for this file (`G10`). A list rather than a case-by-case assertion so
 * that a seventh standing joining or leaving the set is a failure naming both
 * sides, rather than a test that still passes about the six it knew.
 *
 * @return list<string>
 */
function whatDoesNotComeBack(): array
{
    $missing = [];

    foreach (HowItIsHosted::cases() as $standing) {
        if ($standing->didNotComeBack()) {
            $missing[] = $standing->value;
        }
    }

    return $missing;
}

it('N16-R6 — installed and not running is exactly two of the six', function (): void {
    // `Orphaned` counts because it cannot run: the definition is installed and
    // names a program that is not there any more, so a screen listing only the
    // stopped ones shows an operator a shorter list than the truth.
    //
    // `NotHosted` does not count. Nothing was installed, so nothing failed to
    // start — reporting it beside a service that died would be inventing a
    // failure out of a command that only ever ran while a terminal held it.
    //
    // `InstalledUnverified` does not count either, and it is the harder of the
    // two: the manager declined to say. Counting it would report a failure
    // nobody observed, and not counting it is why the word is drawn on the row
    // rather than folded into this answer.
    expect(whatDoesNotComeBack())->toBe(['stopped', 'orphaned']);
});

it('N16-R13 — every standing keeps its own word, so none is drawn as another', function (): void {
    // The whole of what makes six cases better than two buckets. *Not
    // available here* and *off* are the pair that must not collapse, and
    // *installed, unconfirmed* and *running* are the other. A screen draws the
    // word, so what this asks is that there are six of them and they differ.
    $said = [];

    foreach (HowItIsHosted::cases() as $standing) {
        $said[] = $standing->saidOnTheScreen();
    }

    expect($said)->toHaveCount(6)
        ->and(array_unique($said))->toHaveCount(6);
});

it('L7 — every standing names a line, built from the case', function (): void {
    foreach (HowItIsHosted::cases() as $standing) {
        expect($standing->saidOnTheScreen())
            ->toBe(sprintf('stacks.hosting.%s', $standing->value), $standing->name);
    }
});
