<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api\Queries;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Health\Api\Queries\WorstFirst;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;

function row(string $check, Conclusion $conclusion): Finding
{
    return Finding::of(Check::of($check), Category::Vpn, 'A check that ran', $conclusion);
}

/** @return list<string> */
function checksIn(Findings $findings): array
{
    return array_map(
        static fn(Finding $finding): string => $finding->check()->shown(),
        iterator_to_array($findings, preserve_keys: false),
    );
}

it('puts what is broken above what is fine', function (): void {
    $ordered = new WorstFirst()->over(Findings::of(
        row('passed.one', Conclusion::Passed),
        row('failed.one', Conclusion::Failed),
    ));

    expect(checksIn($ordered))->toBe(['failed.one', 'passed.one']);
});

it('puts every conclusion in the order the enum declares', function (): void {
    // Built back to front, so that a sort which did nothing would fail rather
    // than pass on an input that was already right.
    $ordered = new WorstFirst()->over(Findings::of(
        row('skipped.one', Conclusion::Skipped),
        row('passed.one', Conclusion::Passed),
        row('warned.one', Conclusion::Warned),
        row('unverified.one', Conclusion::Unverified),
        row('failed.one', Conclusion::Failed),
    ));

    expect(checksIn($ordered))->toBe([
        'failed.one',
        'unverified.one',
        'warned.one',
        'passed.one',
        'skipped.one',
    ]);
});

it('keeps the order the checks ran in where two turned out the same', function (): void {
    // The tie is not arbitrary: the report arrives in the order the checks ran,
    // and two findings in the same category where one caused the other read
    // differently the other way round.
    $ordered = new WorstFirst()->over(Findings::of(
        row('first.ran', Conclusion::Failed),
        row('second.ran', Conclusion::Failed),
        row('third.ran', Conclusion::Failed),
    ));

    expect(checksIn($ordered))->toBe(['first.ran', 'second.ran', 'third.ran']);
});

it('keeps ties in order across a reordering that moves other rows', function (): void {
    // The case a sort that is stable only by accident gets wrong: the passes
    // move, and the two failures must still be in the order they ran.
    $ordered = new WorstFirst()->over(Findings::of(
        row('passed.one', Conclusion::Passed),
        row('failed.first', Conclusion::Failed),
        row('passed.two', Conclusion::Passed),
        row('failed.second', Conclusion::Failed),
    ));

    expect(checksIn($ordered))->toBe([
        'failed.first',
        'failed.second',
        'passed.one',
        'passed.two',
    ]);
});

it('answers with nothing where a run found nothing', function (): void {
    expect(new WorstFirst()->over(Findings::none())->count())->toBe(0);
});

it('leaves the findings it was given alone', function (): void {
    $findings = Findings::of(
        row('passed.one', Conclusion::Passed),
        row('failed.one', Conclusion::Failed),
    );

    new WorstFirst()->over($findings);

    expect(checksIn($findings))->toBe(['passed.one', 'failed.one']);
});
