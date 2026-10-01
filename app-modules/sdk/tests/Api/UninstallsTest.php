<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\UninstallSaysNothing;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Sdk\Api\UninstallIsUnreadable;
use Modules\Sdk\Api\Uninstalls;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * An `uninstall` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function uninstallSaying(mixed $data): Envelope
{
    return new Envelope(1, 'uninstall', $data);
}

/**
 * One line that goes, with everything a line can carry.
 *
 * @return array<string, mixed>
 */
function aLineThatGoes(): array
{
    return ['name' => 'lemonfiber-sonarr', 'sort' => 'container', 'what' => 'The TV service', 'secret' => false, 'bytes' => 1024];
}

/**
 * The reading of the services, with one line, read in full.
 *
 * @return array<string, mixed>
 */
function aPlainManifest(): array
{
    return [
        'tier' => 'services',
        'removes' => 'The containers and the images',
        'keeps' => 'Your configuration and your library',
        'items' => [aLineThatGoes()],
        'bytes' => 1024,
        'foreign' => [],
        'coming' => [],
        'outside' => [],
        'confidence' => ['complete' => true, 'unread' => []],
        'agreement' => 'services-1-line',
    ];
}

/**
 * An envelope around that manifest, and the removal given.
 *
 * @param  array<string, mixed> $manifest
 * @param  array<string, mixed> $removal
 * @return array<string, mixed>
 */
function anUninstallPayload(array $manifest, array $removal = ['state' => 'surveyed']): array
{
    return ['manifest' => $manifest, 'removal' => $removal];
}

/**
 * Each line the manifest reaches, as a line of its own.
 *
 * @return list<string>
 */
function everyLineTheManifestReaches(WhatTakingItOffComesTo $read): array
{
    $lines = [];

    foreach ($read->items() as $item) {
        $lines[] = sprintf(
            '%s/%s/%s/%s/%s/%s',
            $item->name(),
            $item->sort()->value,
            $item->what(),
            $item->holdsACredential() ? 'secret' : 'plain',
            $item->size()->either(
                known: static fn(int $figure): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d bytes', $figure)),
                unread: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unread'),
            )->said,
            $item->isKept() ? sprintf('kept: %s', $item->whyItIsKept()) : 'going',
        );
    }

    return $lines;
}

/**
 * Everything the manifest says, as one line.
 *
 * @param array<string, mixed> $manifest
 */
function everythingTheManifestCarries(array $manifest): string
{
    $read = Uninstalls::in(uninstallSaying(anUninstallPayload($manifest)))->manifest();
    $lines = everyLineTheManifestReaches($read);

    foreach ($read->foreign() as $foreign) {
        $lines[] = sprintf('foreign %s/%d/%d', $foreign->where(), $foreign->files(), $foreign->bytes());
    }

    foreach ($read->coming() as $coming) {
        $lines[] = sprintf('coming %s/%d', $coming->name(), $coming->progress());
    }

    foreach ($read->outside() as $outside) {
        $lines[] = sprintf('outside %s/%s/%s/%s', $outside->what(), $outside->why(), $outside->byHand(), $outside->wasFound() ? 'found' : 'not found');
    }

    $lines[] = sprintf(
        '%s|%s|%s|%d|%s:%s|%s|%s|%s',
        $read->tier()->value,
        $read->removes(),
        $read->keeps(),
        $read->bytes(),
        $read->confidence()->isComplete() ? 'complete' : 'incomplete',
        implode(',', iterator_to_array($read->confidence(), preserve_keys: false)),
        $read->agreement(),
        $read->volume(),
        $read->copyFirst(),
    );

    return implode("\n", $lines);
}

/**
 * Where the removal got, as one line.
 *
 * @param array<string, mixed> $removal
 */
function whereTheUninstallGot(array $removal): string
{
    return Uninstalls::in(uninstallSaying(anUninstallPayload(aPlainManifest(), $removal)))->removal()->either(
        surveyed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('surveyed'),
        rehearsed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('rehearsed'),
        complete: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): TheWordCarriedOut
            => new TheWordCarriedOut(sprintf('complete %s / %s', implode(',', iterator_to_array($gone, preserve_keys: false)), implode(',', iterator_to_array($credentials, preserve_keys: false)))),
        partial: static function (NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): TheWordCarriedOut {
            $things = [];

            foreach ($left as $thing) {
                $things[] = sprintf('%s/%s/%s', $thing->name(), $thing->why(), $thing->byHand());
            }

            return new TheWordCarriedOut(sprintf('partial %s / %s / %s', implode(',', iterator_to_array($gone, preserve_keys: false)), implode(',', iterator_to_array($credentials, preserve_keys: false)), implode(',', $things)));
        },
    )->said;
}

it('reads a reading with one line going, read in full', function (): void {
    expect(everythingTheManifestCarries(aPlainManifest()))->toBe(implode("\n", [
        'lemonfiber-sonarr/container/The TV service/plain/1024 bytes/going',
        'services|The containers and the images|Your configuration and your library|1024|complete:|services-1-line||',
    ]));
});

it('reads every line apart: kept with why, sizes unread, credentials marked, what is not ours, what is coming, what it cannot take', function (): void {
    $manifest = [
        ...aPlainManifest(),
        'tier' => 'media',
        'items' => [
            ['name' => 'ghcr.io/example/shared:1', 'sort' => 'image', 'what' => 'A shared image', 'secret' => false, 'kept' => 'Another project stands on it'],
            ['name' => 'lemonfiber-net', 'sort' => 'network', 'what' => 'The stack\'s network', 'secret' => false, 'bytes' => null, 'kept' => null],
            ['name' => '/srv/media', 'sort' => 'path', 'what' => 'The library', 'secret' => true, 'bytes' => 0],
        ],
        'foreign' => [['at' => 'photos', 'files' => 0, 'bytes' => 0]],
        'coming' => [['name' => 'A film', 'progress' => 0], ['name' => 'A series', 'progress' => 100]],
        'outside' => [
            ['what' => 'Docker', 'why' => 'Not installed by lemonfiber', 'by_hand' => 'Uninstall Docker Desktop', 'found' => true],
            ['what' => 'Tailscale', 'why' => 'A separate client', 'by_hand' => 'Remove the app', 'found' => false],
        ],
        'confidence' => ['complete' => false, 'unread' => ['The engine did not answer', 'A path was unreadable']],
        'volume' => 'The data location is on a network share',
        'backup' => null,
    ];

    expect(everythingTheManifestCarries($manifest))->toBe(implode("\n", [
        'ghcr.io/example/shared:1/image/A shared image/plain/unread/kept: Another project stands on it',
        'lemonfiber-net/network/The stack\'s network/plain/unread/going',
        '/srv/media/path/The library/secret/0 bytes/going',
        'foreign photos/0/0',
        'coming A film/0',
        'coming A series/100',
        'outside Docker/Not installed by lemonfiber/Uninstall Docker Desktop/found',
        'outside Tailscale/A separate client/Remove the app/not found',
        'media|The containers and the images|Your configuration and your library|1024|incomplete:The engine did not answer,A path was unreadable|services-1-line|The data location is on a network share|',
    ]));
});

it('carries a copy taken first as the stack said it, and says complete where the stack says so beside a sentence', function (): void {
    $manifest = [...aPlainManifest(), 'tier' => 'configuration', 'backup' => 'A copy is taken first', 'volume' => null, 'confidence' => ['complete' => true, 'unread' => ['A note']]];

    expect(everythingTheManifestCarries($manifest))->toEndWith('configuration|The containers and the images|Your configuration and your library|1024|complete:A note|services-1-line||A copy is taken first');
});

it('reads where the removal got: surveyed, rehearsed, complete and partial', function (): void {
    expect(whereTheUninstallGot(['state' => 'surveyed']))->toBe('surveyed')
        ->and(whereTheUninstallGot(['state' => 'confirmed']))->toBe('rehearsed')
        ->and(whereTheUninstallGot(['state' => 'complete', 'gone' => ['a', 'b'], 'credentials' => ['The VPN key']]))->toBe('complete a,b / The VPN key')
        ->and(whereTheUninstallGot(['state' => 'partial', 'gone' => [], 'credentials' => [], 'left' => [['name' => '/srv/x', 'why' => 'Permission denied', 'by_hand' => 'sudo rm -r /srv/x']]]))
        ->toBe('partial  /  / /srv/x/Permission denied/sudo rm -r /srv/x');
});

it('refuses a payload that is not a table', function (): void {
    expect(fn(): AnUninstall => Uninstalls::in(uninstallSaying('an uninstall')))->toThrow(UninstallIsUnreadable::class, 'no readable `data`');
});

it('refuses a manifest or removal that is not there, or not a table', function (): void {
    foreach (['manifest', 'removal'] as $field) {
        expect(fn(): AnUninstall => Uninstalls::in(uninstallSaying(array_diff_key(anUninstallPayload(aPlainManifest()), [$field => true]))))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): AnUninstall => Uninstalls::in(uninstallSaying([...anUninstallPayload(aPlainManifest()), $field => 'none'])))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses a missing or wrongly typed manifest field, naming it', function (): void {
    $spoiled = [
        'tier' => ' ',
        'removes' => 3,
        'keeps' => ' ',
        'items' => 'none',
        'bytes' => '1024',
        'foreign' => 'none',
        'coming' => 'none',
        'outside' => 'none',
        'confidence' => 'full',
        'agreement' => ' ',
    ];

    foreach ($spoiled as $field => $value) {
        expect(fn(): string => everythingTheManifestCarries(array_diff_key(aPlainManifest(), [$field => true])))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $field => $value]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses a list that is a table of names, or a row that is not a table', function (): void {
    foreach (['items', 'foreign', 'coming', 'outside'] as $field) {
        expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $field => ['one' => []]]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $field => ['a row']]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses a line with a field missing or wrongly typed, naming it', function (): void {
    $spoiled = ['name' => ' ', 'sort' => ' ', 'what' => 3, 'secret' => 'no'];

    foreach ($spoiled as $field => $value) {
        expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'items' => [array_diff_key(aLineThatGoes(), [$field => true])]]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'items' => [[...aLineThatGoes(), $field => $value]]]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }

    expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'items' => [[...aLineThatGoes(), 'bytes' => '1 KB']]]))
        ->toThrow(UninstallIsUnreadable::class, 'no readable `bytes`')
        ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'items' => [[...aLineThatGoes(), 'kept' => ' ']]]))
        ->toThrow(UninstallIsUnreadable::class, 'no readable `kept`');
});

it('refuses what is not ours, what is coming and what it cannot take with a field missing or wrongly typed', function (): void {
    $rows = [
        'foreign' => [['at' => 'photos', 'files' => 1, 'bytes' => 1], ['at' => ' ', 'files' => '1', 'bytes' => '1']],
        'coming' => [['name' => 'A film', 'progress' => 1], ['name' => ' ', 'progress' => '1']],
        'outside' => [['what' => 'Docker', 'why' => 'Not ours', 'by_hand' => 'Remove it', 'found' => true], ['what' => ' ', 'why' => ' ', 'by_hand' => ' ', 'found' => 'yes']],
    ];

    foreach ($rows as $list => [$row, $spoiled]) {
        foreach ($spoiled as $field => $value) {
            expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $list => [array_diff_key($row, [$field => true])]]))
                ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
                ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $list => [[...$row, $field => $value]]]))
                ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
        }
    }
});

it('refuses how much was read with a field missing or wrongly typed, or an unread line that is not text', function (): void {
    foreach (['complete' => 'yes', 'unread' => 'none'] as $field => $value) {
        expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'confidence' => array_diff_key(['complete' => true, 'unread' => []], [$field => true])]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'confidence' => [...['complete' => true, 'unread' => []], $field => $value]]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }

    expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'confidence' => ['complete' => false, 'unread' => [3]]]))
        ->toThrow(UninstallIsUnreadable::class, 'no readable `unread`');
});

it('refuses a volume or a copy said blank, or as something other than a sentence', function (): void {
    foreach (['volume', 'backup'] as $field) {
        expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $field => ' ']))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), $field => true]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses a removal with a field missing or wrongly typed, naming it', function (): void {
    $partial = ['state' => 'partial', 'gone' => [], 'credentials' => [], 'left' => []];

    foreach (['state' => ' ', 'gone' => 'all', 'credentials' => 'none', 'left' => 'none'] as $field => $value) {
        expect(fn(): string => whereTheUninstallGot(array_diff_key($partial, [$field => true])))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): string => whereTheUninstallGot([...$partial, $field => $value]))
            ->toThrow(UninstallIsUnreadable::class, sprintf('no readable `%s`', $field));
    }

    expect(fn(): string => whereTheUninstallGot([...$partial, 'gone' => [1]]))->toThrow(UninstallIsUnreadable::class, 'no readable `gone`')
        ->and(fn(): string => whereTheUninstallGot([...$partial, 'left' => [['name' => 'a', 'why' => 'b']]]))->toThrow(UninstallIsUnreadable::class, 'no readable `by_hand`')
        ->and(fn(): string => whereTheUninstallGot(['state' => 'complete', 'gone' => []]))->toThrow(UninstallIsUnreadable::class, 'no readable `credentials`');
});

it('refuses a tier, a sort or a state it has no case for, never reading the nearest one', function (): void {
    expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'tier' => 'everything']))
        ->toThrow(UninstallIsUnreadable::class, 'says `tier` is `everything`, and this app reads `stop`, `services`, `configuration`, `media`')
        ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'items' => [[...aLineThatGoes(), 'sort' => 'volume']]]))
        ->toThrow(UninstallIsUnreadable::class, 'says `sort` is `volume`, and this app reads `container`, `network`, `image`, `path`')
        ->and(fn(): string => whereTheUninstallGot(['state' => 'gone']))
        ->toThrow(UninstallIsUnreadable::class, 'says `state` is `gone`, and this app reads `surveyed`, `confirmed`, `complete`, `partial`');
});

it('refuses figures below none, and a download further along than finished, as the kernel would', function (): void {
    expect(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'bytes' => -1]))->toThrow(UninstallSaysNothing::class, 'its `bytes` at -1')
        ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'coming' => [['name' => 'A film', 'progress' => 101]]]))->toThrow(UninstallSaysNothing::class, 'its `coming.progress` at 101')
        ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'foreign' => [['at' => 'x', 'files' => -1, 'bytes' => 0]]]))->toThrow(UninstallSaysNothing::class, 'its `foreign.files` at -1')
        ->and(fn(): string => everythingTheManifestCarries([...aPlainManifest(), 'confidence' => ['complete' => false, 'unread' => [' ']]]))->toThrow(UninstallSaysNothing::class, 'its `confidence.unread` blank')
        ->and(fn(): string => whereTheUninstallGot(['state' => 'complete', 'gone' => [' '], 'credentials' => []]))->toThrow(UninstallSaysNothing::class, 'its `gone` blank');
});

it('reads only payloads the contract would accept as an uninstall', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UninstallEnvelope', ['api_version' => 1, 'kind' => 'uninstall', 'data' => anUninstallPayload(aPlainManifest())]))->toBe([]);
});
