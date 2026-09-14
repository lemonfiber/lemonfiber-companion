<?php

declare(strict_types=1);

use Modules\Kernel\Api\Stage;

it('N2-R9 — says which of the ten a stalled item is at, as a key', function (): void {
    // The key is built from the case rather than written out, so a stage added
    // to the contract cannot arrive with a line nobody wrote.
    expect(Stage::NotMonitored->saidOnTheScreen())->toBe('health.stage.not-monitored')
        ->and(Stage::Available->saidOnTheScreen())->toBe('health.stage.available');
});

it('the hyphen in a wire value is carried into the key rather than smoothed out', function (): void {
    // `Waiting` already does this and the catalogue already holds such a key.
    // A second spelling rule for the same wire shape is the thing that drifts,
    // so this is asserted rather than left to look accidental.
    foreach (Stage::cases() as $stage) {
        expect($stage->saidOnTheScreen())->toBe(sprintf('health.stage.%s', $stage->value));
    }
});

it('nothing is going to move the two ends of the pipeline by itself', function (): void {
    // The one decision this enum makes. `NotMonitored` is terminal because
    // nothing is watching, `Available` because there is nothing left to watch
    // for — opposite reasons, same answer, and a screen working it out for
    // itself would be a second copy of where the pipeline ends.
    expect(Stage::NotMonitored->stillMoving())->toBeFalse()
        ->and(Stage::Available->stillMoving())->toBeFalse();
});

it('everything between the two ends is work that has stalled part-way', function (): void {
    $moving = array_values(array_filter(
        Stage::cases(),
        static fn(Stage $stage): bool => $stage->stillMoving(),
    ));

    expect(array_map(static fn(Stage $stage): string => $stage->value, $moving))->toBe([
        'monitored',
        'searching',
        'found',
        'grabbed',
        'downloading',
        'downloaded',
        'importing',
        'imported',
    ]);
});

it('the order is the order the work happens in', function (): void {
    // The position is meaning here: two titles stuck at different stages are
    // not equally far along, and a screen sorting them alphabetically would put
    // `available` above `searching`. Moving a case is this failing rather than
    // a screen quietly changing what it says.
    expect(array_map(static fn(Stage $stage): string => $stage->value, Stage::cases()))->toBe([
        'not-monitored',
        'monitored',
        'searching',
        'found',
        'grabbed',
        'downloading',
        'downloaded',
        'importing',
        'imported',
        'available',
    ]);
});
