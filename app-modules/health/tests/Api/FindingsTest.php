<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function array_keys;
use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Health\Api\Category;
use Modules\Health\Api\Check;
use Modules\Health\Api\Conclusion;
use Modules\Health\Api\Finding;
use Modules\Health\Api\Findings;

function finding(string $check, Conclusion $conclusion): Finding
{
    return Finding::of(Check::of($check), Category::Vpn, 'A check that ran', $conclusion);
}

it('holds what it was given, in the order it was given', function (): void {
    // The order a report arrives in is the order the checks ran, which is
    // information: two findings where one caused the other read differently
    // the other way round.
    $findings = Findings::of(
        finding('one.first', Conclusion::Passed),
        finding('two.second', Conclusion::Failed),
    );

    $checks = array_map(
        static fn(Finding $finding): string => $finding->check()->shown(),
        iterator_to_array($findings, preserve_keys: false),
    );

    expect($checks)->toBe(['one.first', 'two.second']);
});

it('counts what it holds', function (): void {
    expect(Findings::of(finding('one.first', Conclusion::Passed))->count())->toBe(1);
});

it('is empty when a run found nothing', function (): void {
    // The healthy case, not a missing report.
    expect(Findings::none()->count())->toBe(0)
        ->and(iterator_to_array(Findings::none(), preserve_keys: false))->toBe([]);
});

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and
    // everything that reorders these reads them by position.
    $findings = Findings::of(
        worst: finding('one.first', Conclusion::Failed),
        rest: finding('two.second', Conclusion::Passed),
    );

    expect(array_keys(iterator_to_array($findings, preserve_keys: true)))->toBe([0, 1]);
});
