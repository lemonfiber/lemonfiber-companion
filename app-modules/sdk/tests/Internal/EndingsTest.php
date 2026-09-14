<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Sdk\Api\UpkeepIsUnreadable;
use Modules\Sdk\Internal\Endings;

/**
 * One service's share of an applied update, with this case's field changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function howOneServiceTookIt(array $differently = []): array
{
    return ['service' => 'jellyfin', 'ending' => 'updated', 'reversal' => 'rollback', ...$differently];
}

/**
 * What a stack reports about the update it applied.
 *
 * @param  array<mixed> $applied
 * @return array<mixed>
 */
function whatAStackReportsItApplied(array $applied): array
{
    return ['applied' => $applied];
}

it('N2-R18 — keeps the four endings apart rather than reporting one failure', function (): void {
    // The whole requirement in one case. *Not fetched*, *not started* and *not
    // reached* are a network, a service and a machine, and an operator sent to
    // the wrong one of those at eleven at night loses the evening.
    $rows = [];

    foreach (Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['service' => 'jellyfin', 'ending' => 'updated']),
        howOneServiceTookIt(['service' => 'sonarr', 'ending' => 'not-fetched']),
        howOneServiceTookIt(['service' => 'radarr', 'ending' => 'not-started']),
        howOneServiceTookIt(['service' => 'prowlarr', 'ending' => 'not-reached']),
    ])) as $took) {
        $rows[] = $took->ending();
    }

    expect($rows)->toBe([
        HowItEnded::Updated,
        HowItEnded::NotFetched,
        HowItEnded::NotStarted,
        HowItEnded::NotReached,
    ]);
});

it('N2-R19 — keeps a rollback and a restore as two offers', function (): void {
    $rows = [];

    foreach (Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['reversal' => 'rollback']),
        howOneServiceTookIt(['reversal' => 'restore']),
    ])) as $took) {
        $rows[] = $took->undo();
    }

    expect($rows)->toBe([HowToUndoIt::Rollback, HowToUndoIt::Restore]);
});

it('reads a stack that has applied nothing as having applied nothing', function (): void {
    expect(Endings::in(whatAStackReportsItApplied([]))->isEmpty())->toBeTrue();
});

it('refuses a reading with no applied block at all', function (): void {
    // Not a stack that has taken no update. `N2-R14`: an absent block is the
    // stack's half of the conversation gone wrong, and *nothing was applied* is
    // the reassuring answer somebody would stop worrying on.
    expect(fn(): object => Endings::in(['state' => 'current']))
        ->toThrow(UpkeepIsUnreadable::class, 'applied');
});

it('refuses an applied block that is not a list', function (): void {
    // Written against the payload rather than through the helper, whose
    // parameter is declared an array: the shape this case is about is one the
    // wire can produce and a typed helper cannot.
    expect(fn(): object => Endings::in(['applied' => 'nothing happened']))
        ->toThrow(UpkeepIsUnreadable::class, 'applied');
});

it('names a service by where it sat rather than by a name it has not got', function (): void {
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(),
        'a string where a service belongs',
    ])))->toThrow(UpkeepIsUnreadable::class, 'Service 2');
});

it('refuses a row with no service to name', function (): void {
    // Built without the field rather than built and then unset, so the shape
    // this case is about is the one the reader is handed.
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([['ending' => 'updated', 'reversal' => 'rollback']])))
        ->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses a service name that is not a word', function (): void {
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['service' => 7]),
    ])))->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses a row that never said how it ended', function (): void {
    // The reassuring default is *updated*, which is the one that has somebody
    // close the screen on a service that never came back.
    // Built without the field rather than built and then unset, so the shape
    // this case is about is the one the reader is handed.
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([['service' => 'jellyfin', 'reversal' => 'rollback']])))
        ->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses an ending this app has no case for', function (): void {
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['ending' => 'mostly']),
    ])))->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses a row that never named a way back', function (): void {
    // `N2-R19` refuses to offer undoing where the stack named neither way, and
    // the contract names one on every row — so an absent one is a payload gone
    // wrong rather than a service that cannot be undone. Defaulting to
    // `rollback` would be this side promising a way back it was not given.
    // Built without the field rather than built and then unset, so the shape
    // this case is about is the one the reader is handed.
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([['service' => 'jellyfin', 'ending' => 'updated']])))
        ->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses a way back this app has no case for', function (): void {
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['reversal' => 'reinstall']),
    ])))->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});

it('refuses an ending that is not a word', function (): void {
    expect(fn(): object => Endings::in(whatAStackReportsItApplied([
        howOneServiceTookIt(['ending' => 4]),
    ])))->toThrow(UpkeepIsUnreadable::class, 'Service 1');
});
