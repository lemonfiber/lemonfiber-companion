<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\ChangeSaysNothing;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereItStopsShort;

use function sprintf;

/** One change as it arrives, with whatever a case wants to change about it. */
function aChangeMade(
    string $did = 'Pointed Sonarr at the new library',
    string $operation = 'reconfigure',
    string $target = 'sonarr',
    int $at = 1_790_142_840,
    HowFarItGoesBack $reversal = HowFarItGoesBack::Whole,
    int $alongside = 1,
): Change {
    return Change::made($did, $operation, $target, WhenItWasMade::at(Instant::atEpochSeconds($at)), $reversal, $alongside);
}

/** One line carried out of the fold. */
final readonly class WhatTheChangeSaidOfItself
{
    public function __construct(public string $said) {}
}

/**
 * Where putting it back stops short, as one line, or the word for nowhere.
 *
 * Named for this file (`G10`), and read through the fold for
 * {@see whatIsGoneFrom()}'s reason: what matters is what reaches a screen.
 */
function whereTheChangeStopsShort(Change $change): string
{
    return $change->stopsShort(
        there: static fn(WhereItStopsShort $where): WhatTheChangeSaidOfItself => new WhatTheChangeSaidOfItself(
            sprintf('%s / %s', $where->why(), $where->instead(
                said: static fn(string $what): WhatTheChangeSaidOfItself => new WhatTheChangeSaidOfItself($what),
                nothing: static fn(): WhatTheChangeSaidOfItself => new WhatTheChangeSaidOfItself('-'),
            )->said),
        ),
        nowhere: static fn(): WhatTheChangeSaidOfItself => new WhatTheChangeSaidOfItself('nowhere'),
    )->said;
}

it('carries what it did, what did it, to what and when', function (): void {
    $change = aChangeMade();

    expect($change->did())->toBe('Pointed Sonarr at the new library')
        ->and($change->operation())->toBe('reconfigure')
        ->and($change->target())->toBe('sonarr')
        ->and($change->when()->isTheSameMomentAs(WhenItWasMade::at(Instant::atEpochSeconds(1_790_142_840))))->toBeTrue();
});

it('N11-R2 — carries how far it could be put back', function (): void {
    expect(aChangeMade(reversal: HowFarItGoesBack::None)->reversal())->toBe(HowFarItGoesBack::None);
});

it('N11-R3 — carries how many changes came with it, this one among them', function (): void {
    expect(aChangeMade(alongside: 4)->alongside())->toBe(4);
});

it('N11-R3 — refuses a count that leaves this change out', function (): void {
    // The operation made this one, so it made at least one. A row claiming it
    // came alone when the count says otherwise is the row that gets undone by
    // itself, leaving half an operation nobody chose.
    expect(fn(): Change => aChangeMade(alongside: 0))->toThrow(ChangeSaysNothing::class, '0 changes');
});

it('takes its words as the stack wrote them, less the space around them', function (): void {
    $change = aChangeMade(did: "  Pointed Sonarr \n", operation: ' seed ', target: ' sonarr ');

    expect($change->did())->toBe('Pointed Sonarr')
        ->and($change->operation())->toBe('seed')
        ->and($change->target())->toBe('sonarr');
});

it('refuses a change with any of its three words blank, naming which', function (): void {
    // A record row that admits something happened and will not say what is
    // worse than no row. The field is named because a record can be long.
    expect(fn(): Change => aChangeMade(did: '  '))->toThrow(ChangeSaysNothing::class, '`did`')
        ->and(fn(): Change => aChangeMade(operation: '  '))->toThrow(ChangeSaysNothing::class, '`operation`')
        ->and(fn(): Change => aChangeMade(target: '  '))->toThrow(ChangeSaysNothing::class, '`target`');
});

it('says nothing stops it short until told otherwise', function (): void {
    expect(whereTheChangeStopsShort(aChangeMade()))->toBe('nowhere');
});

it('N11-R2 — carries where putting it back stops short, and keeps everything else', function (): void {
    $change = aChangeMade(reversal: HowFarItGoesBack::Partial)
        ->stoppingShort(WhereItStopsShort::suggesting('The old library was deleted', 'Restore it from the last backup first'));

    expect(whereTheChangeStopsShort($change))
        ->toBe('The old library was deleted / Restore it from the last backup first')
        // The wither keeps everything else, so giving a change its limits
        // cannot quietly turn a partial reversal into a whole one.
        ->and($change->reversal())->toBe(HowFarItGoesBack::Partial)
        ->and($change->did())->toBe('Pointed Sonarr at the new library')
        ->and($change->operation())->toBe('reconfigure')
        ->and($change->target())->toBe('sonarr')
        ->and($change->when()->isTheSameMomentAs(WhenItWasMade::at(Instant::atEpochSeconds(1_790_142_840))))->toBeTrue()
        ->and($change->alongside())->toBe(1);
});
