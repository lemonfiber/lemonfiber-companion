<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Sdk\Api\RestoreIsUnreadable;
use Modules\Sdk\Api\TheRestore;

use function sprintf;

use Tests\Support\WhatAScopeSays;
use Tests\Support\WhatTheContractAccepts;

/**
 * The copy's own account of itself, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function aManifestSaying(array $changed = []): array
{
    return [
        'created_at' => '2026-09-24T03:00:00Z',
        'data_root' => '/srv/old',
        'members' => [
            ['archive_path' => 'config', 'label' => 'lemonfiber configuration'],
            ['archive_path' => 'services', 'label' => 'service configuration'],
        ],
        'product_version' => '0.9.0',
        'schema' => 1,
        'scope' => ['scope' => 'whole_stack'],
        'sensitive' => true,
        ...$changed,
    ];
}

/**
 * What putting a copy back would do, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function aRestoreListingSaying(array $changed = []): array
{
    return [
        'agreement' => 'restore-the-whole-stack-0.9.0',
        'downgrade' => true,
        'manifest' => aManifestSaying(),
        'relocation' => ['was' => '/srv/old', 'now' => '/srv/new'],
        ...$changed,
    ];
}

/** The listing read out of a `restore` envelope carrying it. */
function aRestoreListingRead(mixed $data): WhatPuttingItBackWouldDo
{
    return TheRestore::listedIn(new Envelope(1, 'restore', $data), ACopy::named('lemonfiber-20260924-0300-full'));
}

/** What a finished restore did, read out of a `restore` envelope carrying it. */
function aRestoreReportRead(mixed $done): ACopyPutBack
{
    return TheRestore::doneIn(new Envelope(1, 'restore', ['would' => aRestoreListingSaying(), 'done' => $done]));
}

/** One line carried out of an arm of where the data goes. */
final readonly class WhereTheRestoredDataWent
{
    public function __construct(public string $said) {}
}

/** Where the data goes, as a line. */
function whereTheRestoredDataGoes(WhereTheDataGoes $data): string
{
    return $data->either(
        whereItWas: static fn(): WhereTheRestoredDataWent => new WhereTheRestoredDataWent('where it was'),
        elsewhere: static fn(ARelocation $moved): WhereTheRestoredDataWent => new WhereTheRestoredDataWent(sprintf('%s to %s', $moved->was(), $moved->now())),
    )->said;
}

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('RestoreEnvelope', ['api_version' => 1, 'kind' => 'restore', 'data' => ['would' => aRestoreListingSaying(), 'done' => null]]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('RestoreEnvelope', ['api_version' => 1, 'kind' => 'restore', 'data' => [
            'would' => aRestoreListingSaying(),
            'done' => ['from_version' => '0.9.0', 'relocated' => null, 'scope' => ['scope' => 'whole_stack']],
        ]]))->toBe([]);
});

it('reads every part of the listing', function (): void {
    $listing = aRestoreListingRead(['would' => aRestoreListingSaying(), 'done' => null]);

    expect($listing->copy()->name())->toBe('lemonfiber-20260924-0300-full')
        ->and($listing->agreement())->toBe('restore-the-whole-stack-0.9.0')
        ->and(WhatAScopeSays::of($listing->scope()))->toBe('whole')
        ->and($listing->takenBy())->toBe('0.9.0')
        ->and($listing->takenAt())->toBe('2026-09-24T03:00:00Z')
        ->and(iterator_to_array($listing->contents(), preserve_keys: false))->toBe(['lemonfiber configuration', 'service configuration'])
        ->and($listing->isOlder())->toBeTrue()
        ->and(whereTheRestoredDataGoes($listing->whereTheDataGoes()))->toBe('/srv/old to /srv/new');
});

it('reads a listing whose data goes back where it came from, however the stack says so', function (array $data): void {
    expect(whereTheRestoredDataGoes(aRestoreListingRead($data)->whereTheDataGoes()))->toBe('where it was');
})->with([
    'said as null' => [['would' => aRestoreListingSaying(['relocation' => null])]],
    'left out' => [['would' => ['agreement' => 'restore-0.9.0', 'downgrade' => false, 'manifest' => aManifestSaying()]]],
]);

it('reads a copy of this version as not older, and one listing nothing inside it as holding nothing', function (): void {
    $listing = aRestoreListingRead(['would' => aRestoreListingSaying(['downgrade' => false, 'manifest' => aManifestSaying(['members' => []])])]);

    expect($listing->isOlder())->toBeFalse()
        ->and($listing->contents())->toHaveCount(0);
});

it('refuses a listing missing a part of it, naming which', function (mixed $data, string $named): void {
    expect(fn(): WhatPuttingItBackWouldDo => aRestoreListingRead($data))->toThrow(RestoreIsUnreadable::class, $named);
})->with([
    'no payload' => ['nothing', '`data`'],
    'no listing' => [['done' => null], '`would`'],
    'a listing that is a word' => [['would' => 'yes'], '`would`'],
    'no manifest' => [['would' => aRestoreListingSaying(['manifest' => null])], '`manifest`'],
    'no agreement' => [['would' => aRestoreListingSaying(['agreement' => null])], '`agreement`'],
    'no version' => [['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['product_version' => 9])])], '`product_version`'],
    'no date' => [['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['created_at' => null])])], '`created_at`'],
    'no word on the version gap' => [['would' => aRestoreListingSaying(['downgrade' => 'no'])], '`downgrade`'],
    'no contents' => [['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['members' => null])])], '`members`'],
    'contents that are a word' => [['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['members' => 'all'])])], '`members`'],
    'contents that are not a list' => [['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['members' => ['first' => ['label' => 'x']]])])], '`members`'],
    'a relocation that is a word' => [['would' => aRestoreListingSaying(['relocation' => '/srv/new'])], '`relocation`'],
    'a relocation with no root it came from' => [['would' => aRestoreListingSaying(['relocation' => ['now' => '/srv/new']])], '`was`'],
    'a relocation with no root it goes to' => [['would' => aRestoreListingSaying(['relocation' => ['was' => '/srv/old', 'now' => 3]])], '`now`'],
]);

it('refuses a thing held that it cannot read, by its position, first or later', function (mixed $members, string $position): void {
    expect(fn(): WhatPuttingItBackWouldDo => aRestoreListingRead(['would' => aRestoreListingSaying(['manifest' => aManifestSaying(['members' => $members])])]))
        ->toThrow(RestoreIsUnreadable::class, $position);
})->with([
    'the first, not a table' => [['config'], 'Entry 0 '],
    'a later one, with no label' => [[['label' => 'lemonfiber configuration'], ['archive_path' => 'services']], 'Entry 1 '],
    'a later one, whose label is not text' => [[['label' => 'lemonfiber configuration'], ['label' => false]], 'Entry 1 '],
]);

it('leaves a blank word to the kernel to refuse', function (array $would): void {
    expect(fn(): WhatPuttingItBackWouldDo => aRestoreListingRead(['would' => $would]))->toThrow(KeepingSaysNothing::class);
})->with([
    'a blank agreement' => [aRestoreListingSaying(['agreement' => ' '])],
    'a blank label' => [aRestoreListingSaying(['manifest' => aManifestSaying(['members' => [['label' => ' ']]])])],
    'a blank root' => [aRestoreListingSaying(['relocation' => ['was' => ' ', 'now' => '/srv/new']])],
]);

it('reads what a finished restore did', function (): void {
    $done = aRestoreReportRead(['from_version' => '0.9.0', 'relocated' => ['was' => '/srv/old', 'now' => '/srv/new'], 'scope' => ['scope' => 'service', 'name' => 'sonarr']]);

    expect(WhatAScopeSays::of($done->scope()))->toBe('service:sonarr')
        ->and($done->takenBy())->toBe('0.9.0')
        ->and(whereTheRestoredDataGoes($done->whereTheDataWent()))->toBe('/srv/old to /srv/new');
});

it('reads a finished restore that put the data back where it came from', function (): void {
    $done = aRestoreReportRead(['from_version' => '0.9.0', 'scope' => ['scope' => 'whole_stack']]);

    expect(whereTheRestoredDataGoes($done->whereTheDataWent()))->toBe('where it was');
});

it('refuses a finished restore that says nothing of what it did, or not all of it', function (mixed $done, string $named): void {
    expect(fn(): ACopyPutBack => aRestoreReportRead($done))->toThrow(RestoreIsUnreadable::class, $named);
})->with([
    'nothing done' => [null, '`done`'],
    'no version' => [['scope' => ['scope' => 'whole_stack']], '`from_version`'],
    'a relocation that is a word' => [['from_version' => '0.9.0', 'relocated' => 'yes', 'scope' => ['scope' => 'whole_stack']], '`relocated`'],
]);
