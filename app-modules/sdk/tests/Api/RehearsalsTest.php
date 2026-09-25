<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Sdk\Api\RehearsalIsUnreadable;
use Modules\Sdk\Api\Rehearsals;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `preview` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function previewSaying(mixed $data): Envelope
{
    return new Envelope(1, 'preview', $data);
}

/**
 * One service a form would leave out, as a stack sends it.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function aServiceItWouldLeaveOut(array $differently = []): array
{
    return [...['id' => 'qbittorrent', 'name' => 'qBittorrent', 'profile' => 'torrent', 'needs' => 'torrent', 'forms' => ['dl']], ...$differently];
}

/**
 * A rehearsal with everything said.
 *
 * @return array<string, mixed>
 */
function aRehearsalInFull(): array
{
    return [
        'forms' => ['dl'],
        'profiles' => ['usenet', 'arr'],
        'services' => ['sabnzbd', 'sonarr'],
        'dropped' => [['profile' => 'torrent', 'needs' => 'torrent']],
        'filtered' => [aServiceItWouldLeaveOut()],
        'footprint' => ['estimated_mib' => 700, 'unestimated' => ['sonarr']],
    ];
}

/** What a rehearsal read, as one line. */
function theRehearsalRead(mixed $data): string
{
    $rehearsal = Rehearsals::in(previewSaying($data));
    $started = [];
    $leftOut = [];
    $unestimated = [];

    foreach ($rehearsal->wouldStart() as $service) {
        $started[] = $service->named();
    }

    foreach ($rehearsal->leftOut() as $service) {
        $leftOut[] = sprintf('%s:%s:%s', $service->name(), $service->needs()->value, $service->id()->named());
    }

    foreach ($rehearsal->footprint()->unestimated() as $service) {
        $unestimated[] = $service->named();
    }

    return sprintf(
        '%s / %s / %d MiB, none for %s',
        implode(',', $started),
        implode(',', $leftOut),
        $rehearsal->footprint()->mebibytes(),
        implode(',', $unestimated),
    );
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PreviewEnvelope', ['api_version' => 1, 'kind' => 'preview', 'data' => aRehearsalInFull()]))->toBe([]);
});

it('reads what would start, every service left out with why, and the estimate with what it leaves out', function (): void {
    expect(theRehearsalRead([...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['id' => 'nzbget', 'name' => 'NZBGet', 'needs' => 'usenet'])]]))
        ->toBe('sabnzbd,sonarr / qBittorrent:torrent:qbittorrent,NZBGet:usenet:nzbget / 700 MiB, none for sonarr');
});

it('reads a start that would bring nothing up, leave nothing out and take nothing', function (): void {
    $rehearsal = Rehearsals::in(previewSaying([...aRehearsalInFull(), 'services' => [], 'filtered' => [], 'footprint' => ['estimated_mib' => 0, 'unestimated' => []]]));

    expect($rehearsal->wouldStart()->isEmpty())->toBeTrue()
        ->and($rehearsal->leftOut())->toHaveCount(0)
        ->and($rehearsal->footprint()->mebibytes())->toBe(0)
        ->and($rehearsal->footprint()->unestimated()->isEmpty())->toBeTrue();
});

it('refuses a payload that is not a table, or a part that is absent or not what the contract says', function (mixed $data, string $field): void {
    expect(fn(): WhatStartingItWouldComeTo => Rehearsals::in(previewSaying($data)))
        ->toThrow(RehearsalIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['nothing', 'data'],
    [array_diff_key(aRehearsalInFull(), ['services' => true]), 'services'],
    [[...aRehearsalInFull(), 'services' => 'sonarr'], 'services'],
    [[...aRehearsalInFull(), 'services' => ['a' => 'sonarr']], 'services'],
    [array_diff_key(aRehearsalInFull(), ['filtered' => true]), 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => 'qbittorrent'], 'filtered'],
    [array_diff_key(aRehearsalInFull(), ['footprint' => true]), 'footprint'],
    [[...aRehearsalInFull(), 'footprint' => 700], 'footprint'],
    [[...aRehearsalInFull(), 'footprint' => ['unestimated' => []]], 'estimated_mib'],
    [[...aRehearsalInFull(), 'footprint' => ['estimated_mib' => '700', 'unestimated' => []]], 'estimated_mib'],
    [[...aRehearsalInFull(), 'footprint' => ['estimated_mib' => -1, 'unestimated' => []]], 'estimated_mib'],
    [[...aRehearsalInFull(), 'footprint' => ['estimated_mib' => 700]], 'unestimated'],
]);

it('refuses the first service left out by its position, not as a list that is missing', function (): void {
    expect(fn(): WhatStartingItWouldComeTo => Rehearsals::in(previewSaying([...aRehearsalInFull(), 'filtered' => ['nzbget']])))
        ->toThrow(RehearsalIsUnreadable::class, 'Entry 0 of the preview\'s `filtered`');
});

it('refuses an entry that is not what the contract says, naming its list and position', function (mixed $data, string $field): void {
    expect(fn(): WhatStartingItWouldComeTo => Rehearsals::in(previewSaying($data)))
        ->toThrow(RehearsalIsUnreadable::class, sprintf('Entry 1 of the preview\'s `%s`', $field));
})->with([
    [[...aRehearsalInFull(), 'services' => ['sabnzbd', ' ']], 'services'],
    [[...aRehearsalInFull(), 'services' => ['sabnzbd', 7]], 'services'],
    [[...aRehearsalInFull(), 'footprint' => ['estimated_mib' => 700, 'unestimated' => ['sonarr', '']]], 'unestimated'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), 'nzbget']], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), array_diff_key(aServiceItWouldLeaveOut(), ['needs' => true])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['name' => ' '])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['id' => ''])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['id' => ' '])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['needs' => 'money'])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['needs' => 7])]], 'filtered'],
    [[...aRehearsalInFull(), 'filtered' => [aServiceItWouldLeaveOut(), aServiceItWouldLeaveOut(['forms' => [' ']])]], 'filtered'],
]);
