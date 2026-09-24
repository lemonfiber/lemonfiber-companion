<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use LogicException;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Sdk\Api\SpaceIsUnreadable;
use Modules\Sdk\Api\WhereTheRoomIs;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `space` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function spaceSaying(mixed $data): Envelope
{
    return new Envelope(1, 'space', $data);
}

/**
 * One data volume, one tree, one download, and nothing that was acted on.
 *
 * @return array<string, mixed>
 */
function aMachineWithRoom(): array
{
    return [
        'volumes' => [aVolumeSaying()],
        'level' => 'ample',
        'halted' => false,
        'consumption' => [['category' => ['of' => 'tree', 'name' => 'movies'], 'reclaim' => 'by_losing_content', 'tally' => ['files' => 3, 'logical' => 30, 'physical' => 20, 'shared' => 1]]],
        'reclaimable' => [],
        'candidates' => [aDownloadSaying()],
        'outsized' => [],
        'interrupted' => [],
        'agreement' => 'space-1',
        'reclaimed' => null,
    ];
}

/**
 * One volume, live, with every figure.
 *
 * @return array<string, mixed>
 */
function aVolumeSaying(): array
{
    return ['role' => 'data', 'at' => '/srv/data', 'point' => '/srv', 'free' => 100, 'limit' => 1_000, 'committed' => 10, 'projected' => 90, 'level' => 'ample', 'reading' => ['as' => 'live']];
}

/**
 * One seeding download.
 *
 * @return array<string, mixed>
 */
function aDownloadSaying(): array
{
    return ['name' => 'Some.Film', 'bytes' => 5, 'standing' => ['standing' => 'seeding', 'ratio' => 150], 'consequence' => 'Your ratio stops growing'];
}

/**
 * The payload with one entry of one list replaced.
 *
 * @param array<string, mixed> $entry
 *
 * @return array<string, mixed>
 */
function withTheFirst(string $list, array $entry): array
{
    return [...aMachineWithRoom(), $list => [$entry]];
}

/**
 * The one volume a payload says, as a line.
 *
 * @param array<string, mixed> $data
 */
function theVolumeRead(array $data): string
{
    $said = '';

    foreach (WhereTheRoomIs::in(spaceSaying($data))->volumes() as $volume) {
        $said = sprintf('%s|%s|%s|%s', $volume->point(), roomFigure($volume->free()), roomFigure($volume->projected()), $volume->reading()->either(
            live: static fn(): WhatTheReaderCarried => new WhatTheReaderCarried('live'),
            asOf: static fn(Instant $at): WhatTheReaderCarried => new WhatTheReaderCarried((string) $at->epochSeconds()),
        )->said);
    }

    return $said;
}

/** One line carried out of an arm. */
final readonly class WhatTheReaderCarried
{
    public function __construct(public string $said) {}
}

/** A figure as text, or `unread`. */
function roomFigure(AnAmountOfRoom $amount): string
{
    return $amount->either(
        known: static fn(int $bytes): WhatTheReaderCarried => new WhatTheReaderCarried((string) $bytes),
        unread: static fn(): WhatTheReaderCarried => new WhatTheReaderCarried('unread'),
    )->said;
}

/**
 * The one download a payload says.
 *
 * @param array<string, mixed> $data
 */
function theDownloadRead(array $data): ADownloadOnDisk
{
    foreach (WhereTheRoomIs::in(spaceSaying($data))->downloads() as $download) {
        return $download;
    }

    throw new LogicException('the payload said no download');
}

/** A download's ratio as text. */
function theRatioRead(ADownloadOnDisk $download): string
{
    return $download->ratio(
        seeding: static fn(ARatio $ratio): WhatTheReaderCarried => $ratio->either(
            read: static fn(string $read): WhatTheReaderCarried => new WhatTheReaderCarried($read),
            none: static fn(): WhatTheReaderCarried => new WhatTheReaderCarried('none'),
        ),
        notSeeding: static fn(): WhatTheReaderCarried => new WhatTheReaderCarried('-'),
    )->said;
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SpaceEnvelope', ['api_version' => 1, 'kind' => 'space', 'data' => aMachineWithRoom()]))->toBe([]);
});

it('reads where the machine stands and whether new work is halted', function (): void {
    $room = WhereTheRoomIs::in(spaceSaying([...aMachineWithRoom(), 'level' => 'exhausted', 'halted' => true]));

    expect([$room->stands()->value, $room->isHalted()])->toBe(['exhausted', true])
        ->and($room)->toBeInstanceOf(WhereTheRoomWent::class);
});

it('N12-R10 — reads a figure the stack could not read as unread, never as nought', function (): void {
    expect(theVolumeRead(aMachineWithRoom()))->toBe('/srv|100|90|live')
        ->and(theVolumeRead(withTheFirst('volumes', [...aVolumeSaying(), 'point' => '', 'free' => null, 'projected' => null])))->toBe('|unread|unread|live')
        ->and(theVolumeRead(withTheFirst('volumes', [...aVolumeSaying(), 'free' => 0])))->toBe('/srv|0|90|live');
});

it('reads a network share\'s figures with when they were taken', function (): void {
    expect(theVolumeRead(withTheFirst('volumes', [...aVolumeSaying(), 'reading' => ['as' => 'as_of', 'at' => 1_790_100_000]])))->toBe('/srv|100|90|1790100000');
});

it('N12-R6 — reads a line by its category, a tree by its name, with both figures', function (): void {
    $lines = [];

    foreach (WhereTheRoomIs::in(spaceSaying([...aMachineWithRoom(), 'consumption' => [
        ['category' => ['of' => 'tree', 'name' => 'movies'], 'reclaim' => 'by_losing_content', 'tally' => ['files' => 3, 'logical' => 30, 'physical' => 20, 'shared' => 1]],
        ['category' => ['of' => 'services'], 'reclaim' => 'you_said_not', 'tally' => ['files' => 1, 'logical' => 5, 'physical' => 5, 'shared' => 0]],
    ]]))->account() as $line) {
        $lines[] = sprintf('%s %s %d/%d %s', $line->about()->value, $line->tree(), $line->occupies()->physical(), $line->occupies()->logical(), $line->costs()->value);
    }

    expect($lines)->toBe(['tree movies 20/30 by_losing_content', 'services  5/5 you_said_not']);
});

it('N12-R1, N12-R2, N12-R3 — reads each standing, the ratio, and what removing costs', function (): void {
    $seeding = theDownloadRead(aMachineWithRoom());
    $never = theDownloadRead(withTheFirst('candidates', ['name' => 'a', 'bytes' => 1, 'standing' => ['standing' => 'never_imported'], 'consequence' => null]));
    $alone = theDownloadRead(withTheFirst('candidates', ['name' => 'b', 'bytes' => 2, 'standing' => ['standing' => 'left_alone']]));

    expect([$seeding->name(), $seeding->bytes(), $seeding->stands()->value, theRatioRead($seeding), $seeding->consequence()])->toBe(['Some.Film', 5, 'seeding', '1.50', 'Your ratio stops growing'])
        ->and([$never->stands()->value, theRatioRead($never), $never->consequence()])->toBe(['never_imported', '-', ''])
        ->and([$alone->stands()->value, theRatioRead($alone), $alone->consequence()])->toBe(['left_alone', '-', '']);
});

it('N12-R2 — reads the figure that means no ratio as none, and the one below it as a ratio', function (): void {
    $none = theDownloadRead(withTheFirst('candidates', [...aDownloadSaying(), 'standing' => ['standing' => 'seeding', 'ratio' => 4_294_967_295]]));
    $below = theDownloadRead(withTheFirst('candidates', [...aDownloadSaying(), 'standing' => ['standing' => 'seeding', 'ratio' => 4_294_967_294]]));

    expect(theRatioRead($none))->toBe('none')
        ->and(theRatioRead($below))->toBe('42949672.94');
});

it('refuses a payload with no data', function (): void {
    expect(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying('nothing')))->toThrow(SpaceIsUnreadable::class, '`data`');
});

it('refuses a list that is missing or not a list, naming it', function (string $list): void {
    $missing = aMachineWithRoom();
    unset($missing[$list]);

    expect(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying($missing)))->toThrow(SpaceIsUnreadable::class, sprintf('`%s`', $list))
        ->and(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying([...aMachineWithRoom(), $list => 'none'])))->toThrow(SpaceIsUnreadable::class, sprintf('`%s`', $list));
})->with(['volumes', 'consumption', 'candidates']);

it('refuses an entry that is not an entry, by its position, rather than dropping it', function (string $list): void {
    expect(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying([...aMachineWithRoom(), $list => ['one']])))
        ->toThrow(SpaceIsUnreadable::class, sprintf('Entry 0 of `%s`', $list));
})->with(['volumes', 'consumption', 'candidates']);

it('refuses a word it has no case for, naming the path and every word it reads', function (array $data, string $where, string $accepts): void {
    expect(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying($data)))->toThrow(SpaceIsUnreadable::class, sprintf('`%s` is', $where))
        ->and(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying($data)))->toThrow(SpaceIsUnreadable::class, $accepts);
})->with([
    'the level' => [[...aMachineWithRoom(), 'level' => 'roomy'], 'level', '`unknown`, `ample`, `advisory`, `warning`, `critical`, `exhausted`.'],
    'a volume\'s level' => [withTheFirst('volumes', [...aVolumeSaying(), 'level' => 'roomy']), 'volumes[0].level', '`unknown`, `ample`'],
    'a role' => [withTheFirst('volumes', [...aVolumeSaying(), 'role' => 'cache']), 'volumes[0].role', '`data`, `services`.'],
    'a reading' => [withTheFirst('volumes', [...aVolumeSaying(), 'reading' => ['as' => 'guessed']]), 'volumes[0].reading.as', '`live`, `as_of`.'],
    'a category' => [withTheFirst('consumption', ['category' => ['of' => 'cache'], 'reclaim' => 'marginally', 'tally' => ['logical' => 1, 'physical' => 1]]), 'consumption[0].category.of', '`tree`, `landing`'],
    'a cost' => [withTheFirst('consumption', ['category' => ['of' => 'landing'], 'reclaim' => 'free', 'tally' => ['logical' => 1, 'physical' => 1]]), 'consumption[0].reclaim', '`by_losing_content`'],
    'a standing' => [withTheFirst('candidates', [...aDownloadSaying(), 'standing' => ['standing' => 'gone']]), 'candidates[0].standing.standing', '`never_imported`, `seeding`, `left_alone`.'],
]);

it('refuses a field that is missing or not what the contract says, naming its path', function (array $data, string $where): void {
    expect(fn(): WhereTheRoomWent => WhereTheRoomIs::in(spaceSaying($data)))->toThrow(SpaceIsUnreadable::class, sprintf('no readable `%s`', $where));
})->with([
    'halted' => [[...aMachineWithRoom(), 'halted' => 'no'], 'halted'],
    'a point' => [withTheFirst('volumes', [...aVolumeSaying(), 'point' => null]), 'volumes[0].point'],
    'a free figure below nothing' => [withTheFirst('volumes', [...aVolumeSaying(), 'free' => -1]), 'volumes[0].free'],
    'a limit that is not a count' => [withTheFirst('volumes', [...aVolumeSaying(), 'limit' => '1000']), 'volumes[0].limit'],
    'what is on its way' => [withTheFirst('volumes', [...aVolumeSaying(), 'committed' => null]), 'volumes[0].committed'],
    'a reading' => [withTheFirst('volumes', [...aVolumeSaying(), 'reading' => 'live']), 'volumes[0].reading'],
    'when a share was read' => [withTheFirst('volumes', [...aVolumeSaying(), 'reading' => ['as' => 'as_of']]), 'volumes[0].reading.at'],
    'a category' => [withTheFirst('consumption', ['reclaim' => 'marginally', 'tally' => ['logical' => 1, 'physical' => 1]]), 'consumption[0].category'],
    'a tree\'s name' => [withTheFirst('consumption', ['category' => ['of' => 'tree'], 'reclaim' => 'marginally', 'tally' => ['logical' => 1, 'physical' => 1]]), 'consumption[0].category.name'],
    'a tally' => [withTheFirst('consumption', ['category' => ['of' => 'landing'], 'reclaim' => 'marginally']), 'consumption[0].tally'],
    'a logical figure' => [withTheFirst('consumption', ['category' => ['of' => 'landing'], 'reclaim' => 'marginally', 'tally' => ['physical' => 1]]), 'consumption[0].tally.logical'],
    'a physical figure' => [withTheFirst('consumption', ['category' => ['of' => 'landing'], 'reclaim' => 'marginally', 'tally' => ['logical' => 1]]), 'consumption[0].tally.physical'],
    'a download\'s name' => [withTheFirst('candidates', [...aDownloadSaying(), 'name' => ' ']), 'candidates[0].name'],
    'a download\'s size' => [withTheFirst('candidates', [...aDownloadSaying(), 'bytes' => -5]), 'candidates[0].bytes'],
    'a standing' => [withTheFirst('candidates', [...aDownloadSaying(), 'standing' => 'seeding']), 'candidates[0].standing'],
    'a seeding ratio' => [withTheFirst('candidates', [...aDownloadSaying(), 'standing' => ['standing' => 'seeding']]), 'candidates[0].standing.ratio'],
    'a blank cost' => [withTheFirst('candidates', [...aDownloadSaying(), 'consequence' => ' ']), 'candidates[0].consequence'],
]);
