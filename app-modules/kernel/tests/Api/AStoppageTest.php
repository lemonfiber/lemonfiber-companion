<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\HowLongIsBelowNothing;
use Modules\Kernel\Api\StoppageSaysNothing;
use Modules\Kernel\Api\WhatStoppedMoving;

it('holds a row as the stack sent it, with the name and the blocking words trimmed', function (): void {
    $row = AStoppage::of(HowItStopped::CompletedNotImported, '  Arrival  ', 1, '  Access denied  ', 90);

    expect($row->how())->toBe(HowItStopped::CompletedNotImported)
        ->and($row->name())->toBe('Arrival')
        ->and($row->items())->toBe(1)
        ->and($row->blocking())->toBe('Access denied')
        ->and($row->heldFor()->inSeconds())->toBe(90);
});

it('holds a row whose service said nothing, and one held for no time at all', function (): void {
    $row = AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 0);

    expect($row->blocking())->toBe('')
        ->and($row->heldFor()->inSeconds())->toBe(0);
});

it('refuses a row naming nothing', function (): void {
    expect(static fn(): AStoppage => AStoppage::of(HowItStopped::Orphaned, '   ', 1, '', 60))
        ->toThrow(StoppageSaysNothing::class, 'naming nothing');
});

it('refuses a row standing for no items, and says how many it claimed', function (): void {
    expect(static fn(): AStoppage => AStoppage::of(HowItStopped::Orphaned, 'Arrival', 0, '', 60))
        ->toThrow(StoppageSaysNothing::class, 'stands for 0 items');
});

it('refuses a row held for less than no time, and says how long it claimed', function (): void {
    expect(static fn(): AStoppage => AStoppage::of(HowItStopped::Orphaned, 'Arrival', 1, '', -1))
        ->toThrow(HowLongIsBelowNothing::class, 'as -1 seconds');
});

it('walks what stopped moving in the order it was given, by position', function (): void {
    $keyed = [
        'first' => AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 60),
        'second' => AStoppage::of(HowItStopped::RedownloadLoop, 'Arrival', 1, '', 60),
    ];

    $walked = iterator_to_array(WhatStoppedMoving::of(...$keyed), preserve_keys: true);

    expect(array_keys($walked))->toBe([0, 1])
        ->and($walked[0]->name())->toBe('Dune')
        ->and($walked[1]->name())->toBe('Arrival')
        ->and(iterator_to_array(WhatStoppedMoving::nothing(), preserve_keys: false))->toBe([]);
});

it('says which kinds want a fix, which is every kind but slow', function (): void {
    $wanting = [];

    foreach (HowItStopped::cases() as $how) {
        $wanting[$how->value] = $how->wantsAFix();
    }

    expect($wanting)->toBe([
        'redownload-loop' => true,
        'repeated-import-failure' => true,
        'completed-not-imported' => true,
        'orphaned' => true,
        'stalled-download' => true,
        'waiting-indefinitely' => true,
        'slow' => false,
    ]);
});

it('names each kind for the screen by its own word', function (): void {
    expect(HowItStopped::StalledDownload->saidOnTheScreen())->toBe('health.stopped.stalled-download');
});
