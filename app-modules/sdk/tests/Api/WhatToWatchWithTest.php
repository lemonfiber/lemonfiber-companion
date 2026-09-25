<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Sdk\Api\ClientsIsUnreadable;
use Modules\Sdk\Api\WhatToWatchWith;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `clients` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function clientsSaying(mixed $data): Envelope
{
    return new Envelope(1, 'clients', $data);
}

/**
 * One device with nothing optional said.
 *
 * @return array<string, mixed>
 */
function aPlainDevice(): array
{
    return ['device' => 'An iPhone', 'client' => 'Jellyfin for iOS', 'support' => 'good'];
}

/**
 * One symptom with one cause.
 *
 * @return array<string, mixed>
 */
function aPlainTrouble(): array
{
    return ['symptom' => 'It buffers', 'causes' => [['because' => 'Weak Wi-Fi', 'tell' => 'Only far away', 'fix' => 'Move closer']]];
}

/**
 * Advice with one device, one symptom and nothing straining.
 *
 * @return array<string, mixed>
 */
function aPlainAdvice(): array
{
    return [
        'devices' => [aPlainDevice()],
        'only_at_home' => 'At home only',
        'nothing_is_installed' => 'Nothing is installed for them',
        'trouble' => [aPlainTrouble()],
    ];
}

/**
 * Everything a payload says, as lines.
 *
 * @param array<string, mixed> $data
 */
function theAdviceRead(array $data): string
{
    $advice = WhatToWatchWith::in(clientsSaying($data));
    $lines = [
        $advice->onlyAtHome(),
        $advice->nothingIsInstalled(),
        sprintf('straining %s|%s|%s', $advice->straining()->preset(), $advice->straining()->caution(), $advice->straining()->instead()),
    ];

    foreach ($advice->devices() as $device) {
        $lines[] = sprintf('%s|%s|%s|%s|%s', $device->device(), $device->client(), $device->support()->value, $device->caution(), $device->instead());
    }

    foreach ($advice->troubles() as $trouble) {
        $lines[] = sprintf('%s (%d)', $trouble->symptom(), $trouble->count());

        foreach ($trouble as $cause) {
            $lines[] = sprintf('%s|%s|%s', $cause->because(), $cause->tell(), $cause->fix());
        }
    }

    return implode("\n", $lines);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ClientsEnvelope', ['api_version' => 1, 'kind' => 'clients', 'data' => aPlainAdvice()]))->toBe([]);
});

it('reads plain advice, with nothing straining and every optional sentence empty', function (): void {
    expect(theAdviceRead(aPlainAdvice()))->toBe(
        "At home only\nNothing is installed for them\nstraining ||\nAn iPhone|Jellyfin for iOS|good||\nIt buffers (1)\nWeak Wi-Fi|Only far away|Move closer",
    );
});

it('reads a device\'s caution and what to use instead, and absent or null as empty', function (): void {
    $poor = [...aPlainDevice(), 'device' => 'An old TV', 'support' => 'poor', 'caution' => 'Subtitles lag', 'instead' => 'A streaming stick'];

    expect(theAdviceRead([...aPlainAdvice(), 'devices' => [aPlainDevice(), $poor]]))->toContain("\nAn iPhone|Jellyfin for iOS|good||\nAn old TV|Jellyfin for iOS|poor|Subtitles lag|A streaming stick\n")
        ->and(theAdviceRead([...aPlainAdvice(), 'devices' => [[...aPlainDevice(), 'caution' => null, 'instead' => null]]]))->toContain("\nAn iPhone|Jellyfin for iOS|good||\n");
});

it('reads every rating the contract names', function (string $support): void {
    expect(theAdviceRead([...aPlainAdvice(), 'devices' => [[...aPlainDevice(), 'support' => $support]]]))->toContain(sprintf('|%s|', $support));
})->with(['workable', 'fallback']);

it('reads what strains playback where it is said, and null as nothing', function (): void {
    expect(theAdviceRead([...aPlainAdvice(), 'straining' => ['preset' => 'Archival', 'caution' => 'No 4K here', 'instead' => 'Choose Balanced']]))->toContain("\nstraining Archival|No 4K here|Choose Balanced\n")
        ->and(theAdviceRead([...aPlainAdvice(), 'straining' => null]))->toContain("\nstraining ||\n");
});

it('reads every symptom and every cause in the stack\'s order, and a symptom with none', function (): void {
    $second = ['symptom' => 'It will not start', 'causes' => []];
    $two = ['symptom' => 'No sound', 'causes' => [['because' => 'Muted', 'tell' => 'Always', 'fix' => 'Unmute'], ['because' => 'Wrong output', 'tell' => 'On a soundbar', 'fix' => 'Pick the soundbar']]];

    expect(theAdviceRead([...aPlainAdvice(), 'devices' => [], 'trouble' => [$two, $second]]))->toBe(
        "At home only\nNothing is installed for them\nstraining ||\nNo sound (2)\nMuted|Always|Unmute\nWrong output|On a soundbar|Pick the soundbar\nIt will not start (0)",
    );
});

it('refuses a payload with no data', function (): void {
    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying('nothing')))->toThrow(ClientsIsUnreadable::class, '`data`');
});

it('refuses a field of the advice itself that is missing or not what the contract says', function (string $field, mixed $said): void {
    $data = $said === 'absent'
        ? array_diff_key(aPlainAdvice(), [$field => true])
        : [...aPlainAdvice(), $field => $said];

    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying($data)))->toThrow(ClientsIsUnreadable::class, sprintf('The clients envelope has no readable `%s`', $field));
})->with([
    ['devices', 'absent'], ['devices', 'phones'], ['trouble', 'absent'], ['trouble', 'none'],
    ['only_at_home', 'absent'], ['only_at_home', ' '], ['only_at_home', 7], ['nothing_is_installed', ''],
    ['straining', 'a lot'],
]);

it('refuses what strains playback where it does not say one of the things it owes', function (string $field, mixed $said): void {
    $straining = ['preset' => 'Archival', 'caution' => 'No 4K here', 'instead' => 'Choose Balanced'];
    $broken = $said === 'absent' ? array_diff_key($straining, [$field => true]) : [...$straining, $field => $said];

    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying([...aPlainAdvice(), 'straining' => $broken])))
        ->toThrow(ClientsIsUnreadable::class, sprintf('The clients envelope has no readable `straining.%s`', $field));
})->with([['preset', 'absent'], ['caution', ' '], ['instead', 7]]);

it('refuses an entry that is not what its list holds, by its position', function (string $list): void {
    $rows = $list === 'devices' ? [aPlainDevice(), 'a phone'] : [aPlainTrouble(), 'it broke'];

    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying([...aPlainAdvice(), $list => $rows])))
        ->toThrow(ClientsIsUnreadable::class, sprintf('Entry 1 of `%s` in the clients envelope is not one', $list));
})->with(['devices', 'trouble']);

it('refuses a field of a device that is missing, blank or not text, by its position', function (string $field, mixed $said): void {
    $broken = $said === 'absent' ? array_diff_key(aPlainDevice(), [$field => true]) : [...aPlainDevice(), $field => $said];

    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying([...aPlainAdvice(), 'devices' => [aPlainDevice(), $broken]])))
        ->toThrow(ClientsIsUnreadable::class, sprintf('Entry 1 of `devices` in the clients envelope has no readable `%s`', $field));
})->with([
    ['device', 'absent'], ['device', ' '], ['client', 7], ['support', 'absent'], ['caution', ' '], ['instead', false],
]);

it('refuses a rating it has no case for, naming the entry and every rating it reads', function (): void {
    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying([...aPlainAdvice(), 'devices' => [aPlainDevice(), [...aPlainDevice(), 'support' => 'excellent']]])))
        ->toThrow(ClientsIsUnreadable::class, 'Entry 1 of `devices` in the clients envelope is rated `excellent`, and this app reads `good`, `workable`, `poor`, `fallback`.');
});

it('refuses a symptom or a cause that does not say what it owes, by the symptom\'s position', function (string $list, string $field, mixed $trouble): void {
    expect(fn(): WhatToWatchOn => WhatToWatchWith::in(clientsSaying([...aPlainAdvice(), 'trouble' => [aPlainTrouble(), $trouble]])))
        ->toThrow(ClientsIsUnreadable::class, sprintf('Entry 1 of `%s` in the clients envelope has no readable `%s`', $list, $field));
})->with([
    ['trouble', 'symptom', ['causes' => []]],
    ['trouble', 'causes', ['symptom' => 'It buffers']],
    ['trouble', 'causes', ['symptom' => 'It buffers', 'causes' => 'the Wi-Fi']],
    ['trouble', 'causes', ['symptom' => 'It buffers', 'causes' => ['the Wi-Fi']]],
    ['causes', 'because', ['symptom' => 'It buffers', 'causes' => [['tell' => 'Always', 'fix' => 'Unmute']]]],
    ['causes', 'tell', ['symptom' => 'It buffers', 'causes' => [['because' => 'Muted', 'tell' => ' ', 'fix' => 'Unmute']]]],
    ['causes', 'fix', ['symptom' => 'It buffers', 'causes' => [['because' => 'Muted', 'tell' => 'Always', 'fix' => 7]]]],
]);
