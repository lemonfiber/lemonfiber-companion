<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\RepairSaysNothing;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatWasMended;

use function sprintf;

/** A repair to have an outcome about. Named for this file (`G10`). */
function aRepairThatWasAgreedTo(string $check = 'storage.one-filesystem'): Repair
{
    return Repair::offered(Check::of($check), 'Move the library', Effects::nothingElse(), Undoing::Possible);
}

/** One outcome folded to a word, so the three facts can be compared at once. */
function whatItCameTo(Mended $mended): string
{
    return $mended->said(
        static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): Code => Code::of($left->either(
            something: static fn(string $what): Code => Code::of(sprintf('%s/%s/%s', $repair->answers()->shown(), $became->value, $what)),
            nothing: static fn(): Code => Code::of(sprintf('%s/%s', $repair->answers()->shown(), $became->value)),
        )->shown()),
    )->shown();
}

it('N2-R5 — carries the repair, what became of it, and what it left', function (): void {
    $stopped = Mended::stopped(aRepairThatWasAgreedTo(), LeftBehind::of('Half of it on the old disk'));

    expect(whatItCameTo($stopped))
        ->toBe('storage.one-filesystem/stopped/Half of it on the old disk');
});

it('an outcome that is not stopped leaves nothing, by construction', function (): void {
    // Not *left nothing because nobody said* — there is no argument for it to
    // be given, so `declined` can never carry a description of debris that does
    // not exist.
    expect(whatItCameTo(Mended::went(aRepairThatWasAgreedTo(), WhatBecameOfIt::Declined)))
        ->toBe('storage.one-filesystem/declined');
});

it('refuses a stopped repair recorded without saying whether it left anything', function (): void {
    // A stopped repair that left nothing is a different morning from one that
    // did, and folding them would lose the difference where it matters most.
    expect(fn(): Mended => Mended::went(aRepairThatWasAgreedTo(), WhatBecameOfIt::Stopped))
        ->toThrow(RepairSaysNothing::class, 'stopped');
});

it('refuses a description of what was left that says nothing', function (): void {
    // It renders as a line of nothing under a heading saying something was
    // left, which is worse than the heading alone.
    expect(fn(): LeftBehind => LeftBehind::of('   '))
        ->toThrow(RepairSaysNothing::class, 'left');
});

it('N2-R5 — only a repair that was put right counts as having changed anything', function (): void {
    // Three of the five changed nothing at all, and *stopped* is the one that
    // both failed and left something — so it answers false here and is still
    // the case that needs saying most.
    $changed = [];

    foreach (WhatBecameOfIt::cases() as $became) {
        if ($became->changedSomething()) {
            $changed[] = $became->value;
        }
    }

    expect($changed)->toBe([WhatBecameOfIt::Fixed->value]);
});

it('N2-R5 — only the two that might work next time are worth another go', function (): void {
    // Declined and would-overwrite will answer the same way again, and a button
    // that does exactly what it did last time teaches people the app is lying.
    $again = [];

    foreach (WhatBecameOfIt::cases() as $became) {
        if ($became->worthAnotherGo()) {
            $again[] = $became->value;
        }
    }

    expect($again)->toBe([WhatBecameOfIt::FixFailed->value, WhatBecameOfIt::Stopped->value]);
});

it('L7 — every outcome names a line, built from the case', function (): void {
    foreach (WhatBecameOfIt::cases() as $became) {
        expect($became->saidOnTheScreen())
            ->toBe(sprintf('health.mended.%s', $became->value), $became->name);
    }
});

it('counts what a run changed, and an empty run is a legitimate answer', function (): void {
    $run = WhatWasMended::of(
        Mended::went(aRepairThatWasAgreedTo('a.check'), WhatBecameOfIt::Fixed),
        Mended::went(aRepairThatWasAgreedTo('b.check'), WhatBecameOfIt::Declined),
        Mended::went(aRepairThatWasAgreedTo('c.check'), WhatBecameOfIt::Fixed),
    );

    expect($run->count())->toBe(3)
        ->and($run->changed())->toBe(2)
        ->and(WhatWasMended::none()->count())->toBe(0)
        ->and(WhatWasMended::none()->changed())->toBe(0);
});

it('builds a list even from named arguments', function (): void {
    // Stored and handed out through the iterator, so the keys escape — which is
    // why this collection reindexes where a reader that merely walks an array
    // does not.
    $run = WhatWasMended::of(
        first: Mended::went(aRepairThatWasAgreedTo('a.check'), WhatBecameOfIt::Fixed),
        then: Mended::went(aRepairThatWasAgreedTo('b.check'), WhatBecameOfIt::Fixed),
    );

    $seen = [];

    foreach ($run as $at => $one) {
        $seen[] = $at;
    }

    expect($seen)->toBe([0, 1]);
});
