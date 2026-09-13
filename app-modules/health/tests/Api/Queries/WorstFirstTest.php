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
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatTheCheckSaid;

function row(string $check, Conclusion $conclusion): Finding
{
    return Finding::of(Check::of($check), Category::Vpn, 'A check that ran', $conclusion, WhatTheCheckSaid::nothingWrong());
}

/** A row the engine graded, which is what `N2-R2` orders by and so the first key. */
function costing(string $check, Severity $severity, Conclusion $conclusion = Conclusion::Failed): Finding
{
    return Finding::of(
        Check::of($check),
        Category::Vpn,
        'A check that ran',
        $conclusion,
        WhatTheCheckSaid::wentWrong(
            Code::of('VPN-3'),
            'Your address was visible to the swarm',
            Remedies::none(),
            $severity,
            Standing::Guided,
        ),
    );
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
    // than pass on an input that was already right. Nothing here was graded, so
    // every row floors at the same severity and the verdict does all the work.
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

it('puts the costlier of two failures first, which the verdict cannot tell apart', function (): void {
    // Three broken things, so the verdict says nothing about which to read
    // first. The engine said one of them puts data at risk and another is
    // merely a broken thing, and an operator reading down the list should meet
    // them in that order.
    $ordered = new WorstFirst()->over(Findings::of(
        costing('an-error', Severity::Error),
        costing('a-critical', Severity::Critical),
        costing('a-warning-severity', Severity::Warning),
    ));

    expect(checksIn($ordered))->toBe(['a-critical', 'an-error', 'a-warning-severity']);
});

it('lets severity outrank the verdict, which is what N2-R2 asks for', function (): void {
    // `N2-R2` orders findings by severity and names nothing else. The engine
    // graded one of these `critical` — data or something outside the machine is
    // at risk — and the other `advisory`, a broken thing that costs nothing.
    // That grading is the engine's judgement and this app does not second-guess
    // it: the critical warning is read first, even though the other one failed.
    $ordered = new WorstFirst()->over(Findings::of(
        costing('an-advisory-failure', Severity::Advisory),
        costing('a-critical-warning', Severity::Critical, Conclusion::Warned),
    ));

    expect(checksIn($ordered))->toBe(['a-critical-warning', 'an-advisory-failure']);
});

it('puts the failure first where the engine graded both the same', function (): void {
    // Severity ties, so the verdict decides — and it has something to say that
    // severity does not. One of these is not working and the other is working
    // badly, which is the order `Conclusion` is declared in.
    $ordered = new WorstFirst()->over(Findings::of(
        costing('a-critical-warning', Severity::Critical, Conclusion::Warned),
        costing('a-critical-failure', Severity::Critical),
    ));

    expect(checksIn($ordered))->toBe(['a-critical-failure', 'a-critical-warning']);
});

it('keeps a check nobody graded below one the engine graded', function (): void {
    // The floor is load-bearing now that severity is read first. A passing
    // check was never graded, and floors at the bottom rather than at nothing,
    // so a failure the engine called a broken thing is above it on severity
    // before the verdict is ever reached.
    $ordered = new WorstFirst()->over(Findings::of(
        row('passed.one', Conclusion::Passed),
        costing('an-error', Severity::Error),
    ));

    expect(checksIn($ordered))->toBe(['an-error', 'passed.one']);
});

it('separates two ungraded checks by the verdict, which is all there is left', function (): void {
    // Neither arm carries a severity, so both floor at the same value and the
    // first key ties. What separates them is the verdict, and `Unverified` sits
    // above `Passed` for the reason the enum gives: a check that could not run
    // must never read as one that passed.
    $ordered = new WorstFirst()->over(Findings::of(
        row('passed.one', Conclusion::Passed),
        row('unverified.one', Conclusion::Unverified),
    ));

    expect(checksIn($ordered))->toBe(['unverified.one', 'passed.one']);
});

it('leaves a check with no severity where the verdict put it', function (): void {
    // A passing check is not graded and a check that could not run has no
    // judgement to report, so neither is ranked against the other. Both tie,
    // and a tie keeps the order the checks ran in.
    $ordered = new WorstFirst()->over(Findings::of(
        row('passed-second', Conclusion::Passed),
        row('passed-first', Conclusion::Passed),
    ));

    expect(checksIn($ordered))->toBe(['passed-second', 'passed-first']);
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
