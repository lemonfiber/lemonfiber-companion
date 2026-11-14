<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Sdk\Api\SpaceIsUnreadable;
use Modules\Sdk\Internal\TheOfferOfRoom;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A reckoning whose offer holds what the case under test is about.
 *
 * @param list<mixed> $reclaimable
 *
 * @return array<string, mixed>
 */
function aReckoningOffering(array $reclaimable, mixed $reclaimed = null): array
{
    return [
        'rehearsed' => false,
        'volumes' => [],
        'level' => 'ample',
        'halted' => false,
        'consumption' => [],
        'reclaimable' => $reclaimable,
        'candidates' => [],
        'outsized' => [],
        'interrupted' => [],
        'agreement' => 'space-7c2e',
        'reclaimed' => $reclaimed,
    ];
}

/**
 * One line of what could be got back.
 *
 * @return array<string, mixed>
 */
function aLineToGetBack(string $of, string $reclaim, int $physical = 30, int $files = 12): array
{
    return ['category' => ['of' => $of], 'reclaim' => $reclaim, 'tally' => ['files' => $files, 'logical' => $physical + 1, 'physical' => $physical, 'shared' => 0]];
}

/**
 * What an answered cleanup reports.
 *
 * @param list<mixed> $left
 *
 * @return array<string, mixed>
 */
function aCleanupThatTook(array $left = [], int $bytes = 30): array
{
    return ['bytes' => $bytes, 'gone' => ['/srv/downloads/a', '/srv/downloads/b'], 'left' => $left, 'rehearsed' => false];
}

/**
 * Each category the offer read, as `word physical/logical files`.
 *
 * @return list<string>
 */
function theCategoriesOffered(TheRoomOnOffer $offer): array
{
    $said = [];

    foreach ($offer as $category) {
        $said[] = sprintf('%s %d/%d %d', $category->about()->value, $category->occupies()->physical(), $category->occupies()->logical(), $category->files());
    }

    return $said;
}

/**
 * A finished cleanup's envelope.
 *
 * @return Envelope<mixed>
 */
function aCleanupSaying(mixed $data): Envelope
{
    return new Envelope(1, 'space', $data);
}

it('stands in for a stack with payloads the contract would accept', function (array $data): void {
    expect(WhatTheContractAccepts::complaintsAbout('SpaceEnvelope', ['api_version' => 1, 'kind' => 'space', 'data' => $data]))->toBe([]);
})->with([
    'an offer' => [aReckoningOffering([aLineToGetBack('orphaned', 'the_easy_win'), aLineToGetBack('seeding', 'at_the_cost_of_ratio')])],
    'a cleanup answered' => [aReckoningOffering([], aCleanupThatTook([['at' => '/srv/downloads/c', 'why' => 'Permission denied']]))],
]);

it('reads each category that costs nothing and holds anything, in the order sent, under the offer\'s name', function (): void {
    $offer = TheOfferOfRoom::in(aReckoningOffering([
        aLineToGetBack('extracted', 'already_have_it', 5, 1),
        aLineToGetBack('seeding', 'at_the_cost_of_ratio'),
        aLineToGetBack('unmanaged', 'you_said_not'),
        aLineToGetBack('orphaned', 'the_easy_win', 0, 0),
        aLineToGetBack('orphaned', 'the_easy_win', 0, 2),
        aLineToGetBack('extracted', 'already_have_it', 7, 0),
    ]));

    expect($offer->agreement())->toBe('space-7c2e')
        ->and(theCategoriesOffered($offer))->toBe(['extracted 5/6 1', 'orphaned 0/1 2', 'extracted 7/8 0']);
});

it('reads an offer with nothing on it as an offer still named', function (): void {
    $offer = TheOfferOfRoom::in(aReckoningOffering([]));

    expect([$offer->agreement(), theCategoriesOffered($offer)])->toBe(['space-7c2e', []]);
});

it('skips a line that costs something without reading the rest of it', function (): void {
    expect(theCategoriesOffered(TheOfferOfRoom::in(aReckoningOffering([['reclaim' => 'by_losing_content']]))))->toBe([]);
});

it('refuses an offer it cannot read, naming where', function (array $data, string $refused): void {
    expect(fn(): TheRoomOnOffer => TheOfferOfRoom::in($data))->toThrow(SpaceIsUnreadable::class, $refused);
})->with([
    'no list' => [[...aReckoningOffering([]), 'reclaimable' => null], 'no readable `reclaimable`'],
    'an entry that is not one' => [aReckoningOffering([aLineToGetBack('orphaned', 'the_easy_win'), 'one']), 'Entry 1 of `reclaimable`'],
    'no name' => [[...aReckoningOffering([]), 'agreement' => ' '], 'no readable `agreement`'],
    'no cost' => [aReckoningOffering([['category' => ['of' => 'orphaned']]]), 'no readable `reclaimable[0].reclaim`'],
    'a cost it has no case for' => [aReckoningOffering([aLineToGetBack('orphaned', 'free')]), '`reclaimable[0].reclaim` is `free`'],
    'no category' => [aReckoningOffering([['reclaim' => 'the_easy_win', 'tally' => []]]), 'no readable `reclaimable[0].category`'],
    'a category with no word' => [aReckoningOffering([['category' => [], 'reclaim' => 'the_easy_win']]), 'no readable `reclaimable[0].category.of`'],
    'a category it has no case for' => [aReckoningOffering([aLineToGetBack('cache', 'the_easy_win')]), '`reclaimable[0].category.of` is `cache`'],
    'no tally' => [aReckoningOffering([['category' => ['of' => 'orphaned'], 'reclaim' => 'the_easy_win']]), 'no readable `reclaimable[0].tally`'],
    'a physical figure below nothing' => [aReckoningOffering([aLineToGetBack('orphaned', 'the_easy_win', -1)]), 'no readable `reclaimable[0].tally.physical`'],
    'a file count that is not one' => [aReckoningOffering([['category' => ['of' => 'orphaned'], 'reclaim' => 'the_easy_win', 'tally' => ['files' => '12', 'logical' => 1, 'physical' => 1]]]), 'no readable `reclaimable[0].tally.files`'],
    'no logical figure' => [aReckoningOffering([['category' => ['of' => 'orphaned'], 'reclaim' => 'the_easy_win', 'tally' => ['files' => 1, 'physical' => 1]]]), 'no readable `reclaimable[0].tally.logical`'],
]);

it('reads what a cleanup came to: the room returned, how many items went and why each left stayed', function (): void {
    $back = TheOfferOfRoom::cameBackIn(aCleanupSaying(aReckoningOffering([], aCleanupThatTook([
        ['at' => '/srv/downloads/c', 'why' => 'Permission denied'],
        ['at' => '/srv/downloads/d', 'why' => 'Read-only file system'],
    ]))));

    expect([$back->bytes(), $back->went(), iterator_to_array($back->left(), preserve_keys: false)])->toBe([30, 2, ['Permission denied', 'Read-only file system']]);
});

it('refuses a cleanup it cannot read, naming where', function (mixed $data, string $refused): void {
    expect(fn(): TheRoomGivenBack => TheOfferOfRoom::cameBackIn(aCleanupSaying($data)))->toThrow(SpaceIsUnreadable::class, $refused);
})->with([
    'no data' => ['nothing', 'no readable `data`'],
    'nothing reclaimed' => [aReckoningOffering([]), 'no readable `reclaimed`'],
    'no room returned' => [aReckoningOffering([], [...aCleanupThatTook(), 'bytes' => null]), 'no readable `reclaimed.bytes`'],
    'room below nothing' => [aReckoningOffering([], aCleanupThatTook(bytes: -1)), 'no readable `reclaimed.bytes`'],
    'nothing said gone' => [aReckoningOffering([], [...aCleanupThatTook(), 'gone' => null]), 'no readable `reclaimed.gone`'],
    'nothing said left' => [aReckoningOffering([], [...aCleanupThatTook(), 'left' => 'none']), 'no readable `reclaimed.left`'],
    'an entry left that is not one' => [aReckoningOffering([], aCleanupThatTook([['why' => 'Permission denied'], 'one'])), 'Entry 1 of `reclaimed.left`'],
    'an item left with no reason' => [aReckoningOffering([], aCleanupThatTook([['at' => '/srv/downloads/c', 'why' => ' ']])), 'no readable `reclaimed.left[0].why`'],
]);
