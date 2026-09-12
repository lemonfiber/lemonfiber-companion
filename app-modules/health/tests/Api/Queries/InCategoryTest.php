<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api\Queries;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Health\Api\Category;
use Modules\Health\Api\Check;
use Modules\Health\Api\Conclusion;
use Modules\Health\Api\Finding;
use Modules\Health\Api\Findings;
use Modules\Health\Api\Queries\InCategory;
use Modules\Health\Api\Queries\WorstFirst;

// Named apart from WorstFirstTest's `row` and `checksIn`: a module's test files
// share one namespace, so a second `row` here is a fatal at load rather than a
// shadow.

function found(string $check, Category $category): Finding
{
    return Finding::of(Check::of($check), $category, 'A check that ran', Conclusion::Passed);
}

/** @return list<string> */
function namesIn(Findings $findings): array
{
    return array_map(
        static fn(Finding $finding): string => $finding->check()->shown(),
        iterator_to_array($findings, preserve_keys: false),
    );
}

it('keeps the family asked for and drops the rest', function (): void {
    $narrowed = new InCategory(Category::Network)->over(Findings::of(
        found('storage.room', Category::Storage),
        found('network.ports', Category::Network),
        found('vpn.leak', Category::Vpn),
        found('network.bindings', Category::Network),
    ));

    expect(namesIn($narrowed))->toBe(['network.ports', 'network.bindings']);
});

it('leaves the order it was given alone', function (): void {
    // The findings arrive in the order the checks ran, and that is information
    // this has no business rearranging — it is also what makes composing with
    // WorstFirst mean something, because the caller can say which happened
    // first.
    $narrowed = new InCategory(Category::Storage)->over(Findings::of(
        found('storage.third', Category::Storage),
        found('network.ports', Category::Network),
        found('storage.first', Category::Storage),
        found('storage.second', Category::Storage),
    ));

    expect(namesIn($narrowed))->toBe(['storage.third', 'storage.first', 'storage.second']);
});

it('answers with nothing where the family raised nothing', function (): void {
    // A category the product recognises where this stack found nothing, or
    // where the checks that fill it do not exist yet. Not a missing report.
    $narrowed = new InCategory(Category::Providers)->over(Findings::of(
        found('storage.room', Category::Storage),
    ));

    expect($narrowed->count())->toBe(0);
});

it('answers with nothing where there was nothing to narrow', function (): void {
    expect(new InCategory(Category::Providers)->over(Findings::none())->count())->toBe(0);
});

it('composes with WorstFirst, in the order it is written', function (): void {
    $all = Findings::of(
        Finding::of(Check::of('network.ports'), Category::Network, 'Ports', Conclusion::Passed),
        Finding::of(Check::of('storage.room'), Category::Storage, 'Room', Conclusion::Failed),
        Finding::of(Check::of('network.bindings'), Category::Network, 'Bindings', Conclusion::Failed),
    );

    $worstInNetwork = new WorstFirst()->over(new InCategory(Category::Network)->over($all));

    // The storage failure is gone even though it is the worst thing in the
    // report: narrowing happened first, which is what the reading order says.
    expect(namesIn($worstInNetwork))->toBe(['network.bindings', 'network.ports']);
});
