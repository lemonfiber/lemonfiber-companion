<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use LogicException;
use Modules\Sdk\Api\StoredIsUnreadable;
use Modules\Sdk\Api\WhatIsStored;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `stored` envelope holding whatever the case under test is about.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function storedSaying(array $data): Envelope
{
    return new Envelope(1, 'stored', $data);
}

/**
 * One of each list, and a removal nobody has asked for.
 *
 * @return array{roots: list<array<string, mixed>>, kept: list<array<string, mixed>>, beside: array<array-key, array<string, mixed>>, removal: array<string, string>}
 */
function whatALoftStores(): array
{
    return [
        'roots' => [['at' => '/srv/lemonfiber', 'what' => 'Everything the stack writes']],
        'kept' => [
            ['what' => 'The VPN credentials', 'at' => '/srv/lemonfiber/secrets/vpn', 'why' => 'So the tunnel can be raised', 'secret' => true],
            ['what' => 'Sonarr\'s settings', 'at' => '/srv/lemonfiber/config/sonarr', 'why' => 'So Sonarr starts as it was left', 'secret' => false],
        ],
        'beside' => [['what' => '/srv/media', 'why' => 'Your library']],
        'removal' => ['state' => 'not-asked'],
    ];
}

/**
 * The first row of one of the three lists, for a case to break.
 *
 * @return array<string, mixed>
 */
function theFirstRowOf(string $list): array
{
    return match ($list) {
        'roots' => whatALoftStores()['roots'][0],
        'kept' => whatALoftStores()['kept'][0],
        'beside' => whatALoftStores()['beside'][0],
        default => throw new LogicException(sprintf('`%s` is not a list this payload carries', $list)),
    };
}

/**
 * Everything read, one line an entry, so a case can say what it expects in one place.
 *
 * @param  array<mixed>  $data
 * @return list<string>
 */
function everythingStored(array $data): array
{
    $keeps = WhatIsStored::in(storedSaying($data));
    $said = [];

    foreach ($keeps->roots() as $root) {
        $said[] = sprintf('root %s %s', $root->where(), $root->what());
    }

    foreach ($keeps->kept() as $one) {
        $said[] = sprintf('kept %s %s %s %s', $one->what(), $one->where(), $one->why(), $one->secret()->value);
    }

    foreach ($keeps->beside() as $one) {
        $said[] = sprintf('beside %s %s', $one->what(), $one->why());
    }

    return $said;
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StoredEnvelope', ['api_version' => 1, 'kind' => 'stored', 'data' => whatALoftStores()]))->toBe([]);
});

it('N6-R7 — reads every list in order, and each secret flag the right way round', function (): void {
    expect(everythingStored(whatALoftStores()))->toBe([
        'root /srv/lemonfiber Everything the stack writes',
        'kept The VPN credentials /srv/lemonfiber/secrets/vpn So the tunnel can be raised secret',
        'kept Sonarr\'s settings /srv/lemonfiber/config/sonarr So Sonarr starts as it was left plain',
        'beside /srv/media Your library',
    ]);
});

it('N6-R7 — reads nothing it is not asked to, a value beside a secret included', function (): void {
    $data = whatALoftStores();
    $data['kept'][0] = [...$data['kept'][0], 'value' => 'hunter2'];

    expect(implode("\n", everythingStored($data)))->not->toContain('hunter2');
});

it('reads a list that arrived as an object by position, not by key', function (): void {
    $data = whatALoftStores();
    $data['beside'] = ['plex' => ['what' => '/srv/media', 'why' => 'Your library']];

    expect(everythingStored($data))->toContain('beside /srv/media Your library');
});

it('refuses an envelope with no payload', function (): void {
    expect(fn(): mixed => WhatIsStored::in(new Envelope(1, 'stored', 'nothing')))->toThrow(StoredIsUnreadable::class, '`data`');
});

it('refuses a list that is missing or is not a list, naming it', function (string $list): void {
    $missing = whatALoftStores();
    unset($missing[$list]);
    $scalar = whatALoftStores();
    $scalar[$list] = 'none';

    expect(fn(): mixed => WhatIsStored::in(storedSaying($missing)))->toThrow(StoredIsUnreadable::class, sprintf('`%s`', $list))
        ->and(fn(): mixed => WhatIsStored::in(storedSaying($scalar)))->toThrow(StoredIsUnreadable::class, sprintf('`%s`', $list));
})->with(['roots', 'kept', 'beside']);

it('refuses a row that is not a row, by its position, rather than dropping it', function (string $list): void {
    $data = whatALoftStores();
    $data[$list] = [theFirstRowOf($list), 'the second'];

    expect(fn(): mixed => WhatIsStored::in(storedSaying($data)))->toThrow(StoredIsUnreadable::class, sprintf('Entry 1 of `%s`', $list));
})->with(['roots', 'kept', 'beside']);

it('refuses a field that is missing, blank or not text, naming the list, the field and the row', function (string $list, string $field): void {
    $row = theFirstRowOf($list);
    $withoutIt = $row;
    unset($withoutIt[$field]);

    foreach ([$withoutIt, [...$row, $field => '  '], [...$row, $field => 7]] as $broken) {
        $data = whatALoftStores();
        $data[$list] = [$broken];

        expect(fn(): mixed => WhatIsStored::in(storedSaying($data)))
            ->toThrow(StoredIsUnreadable::class, sprintf('Entry 0 of `%s` in the stored envelope has no readable `%s`', $list, $field));
    }
})->with([
    ['roots', 'at'], ['roots', 'what'],
    ['kept', 'what'], ['kept', 'at'], ['kept', 'why'],
    ['beside', 'what'], ['beside', 'why'],
]);

it('N6-R7 — refuses a secret flag that is missing or not a yes or no, rather than calling it plain', function (): void {
    $row = whatALoftStores()['kept'][0];
    $withoutIt = $row;
    unset($withoutIt['secret']);

    foreach ([$withoutIt, [...$row, 'secret' => 'yes'], [...$row, 'secret' => 1]] as $broken) {
        $data = whatALoftStores();
        $data['kept'] = [$broken];

        expect(fn(): mixed => WhatIsStored::in(storedSaying($data)))
            ->toThrow(StoredIsUnreadable::class, 'Entry 0 of `kept` in the stored envelope has no readable `secret`');
    }
});
