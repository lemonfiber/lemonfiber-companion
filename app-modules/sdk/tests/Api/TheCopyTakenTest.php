<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\BackupIsUnreadable;
use Modules\Sdk\Api\ScopeIsUnreadable;
use Modules\Sdk\Api\TheCopyTaken;
use Tests\Support\WhatAScopeSays;
use Tests\Support\WhatTheContractAccepts;

/**
 * What a stack reports of a copy, changed where a case says.
 *
 * @param  array<mixed> $changed
 * @return array<mixed>
 */
function aBackupReportSaying(array $changed = []): array
{
    return [
        'scope' => ['scope' => 'service', 'name' => 'sonarr'],
        'path' => '/srv/lemonfiber/backups/lemonfiber-20260925-0300-sonarr.tar.zst',
        'pruned' => ['lemonfiber-20260901-0300-sonarr'],
        'pace' => ['moved' => 1_200, 'budget' => 629_145_600, 'brisk' => true],
        'rehearsed' => false,
        'sensitive' => true,
        ...$changed,
    ];
}

/** That report, in the envelope it arrives in. */
function aCopyTakenReading(mixed $data): ACopyTaken
{
    return TheCopyTaken::in(new Envelope(1, 'backup', $data));
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BackupEnvelope', ['api_version' => 1, 'kind' => 'backup', 'data' => aBackupReportSaying()]))->toBe([]);
});

it('reads every part of the report', function (): void {
    $taken = aCopyTakenReading(aBackupReportSaying());

    expect(WhatAScopeSays::of($taken->scope()))->toBe('service:sonarr')
        ->and(iterator_to_array($taken->pruned(), preserve_keys: false))->toBe(['lemonfiber-20260901-0300-sonarr'])
        ->and($taken->pace()->moved())->toBe(1_200)
        ->and($taken->pace()->budget())->toBe(629_145_600)
        ->and($taken->pace()->isBrisk())->toBeTrue()
        ->and($taken->holds())->toBe(WhetherItHoldsASecret::Secret)
        ->and($taken->was())->toBe(WhetherItWasRehearsed::CarriedOut);
});

it('reads a rehearsal as one, a copy past the budget as past it, and one holding nothing secret as that', function (): void {
    $taken = aCopyTakenReading(aBackupReportSaying([
        'rehearsed' => true,
        'sensitive' => false,
        'pace' => ['moved' => 900_000_000, 'budget' => 629_145_600, 'brisk' => false],
        'pruned' => [],
    ]));

    expect($taken->was())->toBe(WhetherItWasRehearsed::Rehearsed)
        ->and($taken->holds())->toBe(WhetherItHoldsASecret::Plain)
        ->and($taken->pace()->isBrisk())->toBeFalse()
        ->and($taken->pace()->moved())->toBe(900_000_000)
        ->and($taken->pruned())->toHaveCount(0);
});

it('reads the copies it removed in the stack\'s order, by position however they arrived', function (): void {
    $taken = aCopyTakenReading(aBackupReportSaying(['pruned' => ['lemonfiber-20260901-0300-sonarr', 'lemonfiber-20260902-0300-sonarr']]));

    expect(iterator_to_array($taken->pruned(), preserve_keys: false))->toBe(['lemonfiber-20260901-0300-sonarr', 'lemonfiber-20260902-0300-sonarr']);
});

it('refuses a report missing a part of it, naming which, rather than reading it short', function (mixed $data, string $named): void {
    expect(fn(): ACopyTaken => aCopyTakenReading($data))->toThrow(BackupIsUnreadable::class, $named);
})->with([
    'no payload' => ['nothing', '`data`'],
    'no removed copies' => [aBackupReportSaying(['pruned' => null]), '`pruned`'],
    'removed copies that are a word' => [aBackupReportSaying(['pruned' => 'none']), '`pruned`'],
    'removed copies that are not a list' => [aBackupReportSaying(['pruned' => ['first' => 'lemonfiber-20260901-0300-sonarr']]), '`pruned`'],
    'no pace' => [aBackupReportSaying(['pace' => null]), '`pace`'],
    'no bytes moved' => [aBackupReportSaying(['pace' => ['budget' => 1, 'brisk' => true]]), '`moved`'],
    'bytes moved that are not a figure' => [aBackupReportSaying(['pace' => ['moved' => '1', 'budget' => 1, 'brisk' => true]]), '`moved`'],
    'no budget' => [aBackupReportSaying(['pace' => ['moved' => 1, 'brisk' => true]]), '`budget`'],
    'no word on the budget' => [aBackupReportSaying(['pace' => ['moved' => 1, 'budget' => 1]]), '`brisk`'],
    'a word on the budget that is not yes or no' => [aBackupReportSaying(['pace' => ['moved' => 1, 'budget' => 1, 'brisk' => 'yes']]), '`brisk`'],
    'no word on a rehearsal' => [aBackupReportSaying(['rehearsed' => null]), '`rehearsed`'],
    'no word on credentials' => [aBackupReportSaying(['sensitive' => 1]), '`sensitive`'],
]);

it('refuses a removed copy that is not a name, by its position, first or later', function (mixed $pruned, string $position): void {
    expect(fn(): ACopyTaken => aCopyTakenReading(aBackupReportSaying(['pruned' => $pruned])))->toThrow(BackupIsUnreadable::class, $position);
})->with([
    'the first' => [[7], 'Entry 0 '],
    'a later one' => [['lemonfiber-20260901-0300-sonarr', null], 'Entry 1 '],
]);

it('leaves a blank name and a size below nothing to the kernel to refuse', function (array $changed): void {
    expect(fn(): ACopyTaken => aCopyTakenReading(aBackupReportSaying($changed)))->toThrow(KeepingSaysNothing::class);
})->with([
    'a blank removed copy' => [['pruned' => ['  ']]],
    'a size below nothing' => [['pace' => ['moved' => -1, 'budget' => 1, 'brisk' => true]]],
    'a budget below nothing' => [['pace' => ['moved' => 1, 'budget' => -1, 'brisk' => true]]],
]);

it('refuses a scope it cannot read with the scope\'s own refusal', function (): void {
    expect(fn(): ACopyTaken => aCopyTakenReading(aBackupReportSaying(['scope' => null])))->toThrow(ScopeIsUnreadable::class);
});
