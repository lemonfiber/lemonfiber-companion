<?php

declare(strict_types=1);

use Modules\Health\Api\Queries\TheCauseBeforeItsSymptoms;
use Modules\Kernel\Api\Because;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;

/** One finding, optionally explained by another check. Named for this file (`G10`). */
function aCheckThatRan(string $named, string $explainedBy = ''): Finding
{
    $finding = Finding::of(
        Check::of($named),
        Category::Vpn,
        $named,
        Conclusion::Failed,
        WhatTheCheckSaid::nothingWrong(),
        WhoPutItThere::bundled(),
    );

    return $explainedBy === ''
        ? $finding
        : $finding->because(Because::theCheck(Check::of($explainedBy)));
}

/**
 * The order a run comes out in, as check names a case can compare.
 *
 * @return list<string>
 */
function arrangedAs(Findings $findings): array
{
    $named = [];

    foreach (new TheCauseBeforeItsSymptoms()->over($findings) as $finding) {
        $named[] = $finding->check()->shown();
    }

    return $named;
}

it('G4-R3 — puts a cause immediately before what it explains', function (): void {
    // The reading this exists to prevent: four separate problems, ordered by
    // severity, each saying *because of this other thing*, with that other
    // thing somewhere else in the list. An operator reads four and goes looking
    // for the one that caused them.
    $run = Findings::of(
        aCheckThatRan('jellyfin.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('sonarr.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('vpn.up'),
    );

    expect(arrangedAs($run))->toBe(['vpn.up', 'jellyfin.reachable', 'sonarr.reachable']);
});

it('G4-R3 — keeps the symptoms it was handed in the order it was handed them', function (): void {
    // Run after `WorstFirst`, so the symptoms under a cause are already
    // worst-first. Reordering them here would be this query having a second
    // opinion about severity, which is not its question.
    $run = Findings::of(
        aCheckThatRan('vpn.up'),
        aCheckThatRan('sonarr.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('jellyfin.reachable', explainedBy: 'vpn.up'),
    );

    expect(arrangedAs($run))->toBe(['vpn.up', 'sonarr.reachable', 'jellyfin.reachable']);
});

it('G4-R3 — reorders and does not narrow', function (): void {
    // Every finding that went in comes out. A stack with the tunnel down and
    // four services unreachable is worse than one with the tunnel down, and a
    // screen showing only the cause would have lost that.
    $run = Findings::of(
        aCheckThatRan('jellyfin.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('disk.space'),
        aCheckThatRan('vpn.up'),
    );

    expect(arrangedAs($run))->toHaveCount(3)
        ->and(arrangedAs($run))->toContain('disk.space');
});

it('leaves a finding whose cause this run did not report exactly where it was', function (): void {
    // A check can name one that passed, or one a narrowed list left out. There
    // is nothing for it to sit under, so it keeps its own place rather than
    // being swept to the end — being explained by something absent is not a
    // reason to read it last, and dropping it would make this query narrow,
    // which is the one thing it must not do.
    $run = Findings::of(
        aCheckThatRan('jellyfin.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('disk.space'),
    );

    expect(arrangedAs($run))->toBe(['jellyfin.reachable', 'disk.space']);
});

it('leaves a run where nothing explains anything alone', function (): void {
    $run = Findings::of(aCheckThatRan('disk.space'), aCheckThatRan('vpn.up'));

    expect(arrangedAs($run))->toBe(['disk.space', 'vpn.up']);
});

it('leaves an empty run empty', function (): void {
    expect(arrangedAs(Findings::none()))->toBe([]);
});

it('arranges two causes and their own symptoms without mixing them', function (): void {
    $run = Findings::of(
        aCheckThatRan('vpn.up'),
        aCheckThatRan('disk.space'),
        aCheckThatRan('sonarr.reachable', explainedBy: 'vpn.up'),
        aCheckThatRan('library.writable', explainedBy: 'disk.space'),
    );

    expect(arrangedAs($run))
        ->toBe(['vpn.up', 'sonarr.reachable', 'disk.space', 'library.writable']);
});
