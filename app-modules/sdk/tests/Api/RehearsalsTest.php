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
 * A rehearsal with everything said.
 *
 * @return array<string, mixed>
 */
function aRehearsalInFull(): array
{
    return [
        'forms' => ['dl'],
        'profiles' => ['usenet', 'torrent'],
        'services' => ['sabnzbd', 'sonarr'],
        'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['profile' => 'nzb', 'needs' => 'usenet']],
    ];
}

/** What a rehearsal read, as one line. */
function theRehearsalRead(mixed $data): string
{
    $rehearsal = Rehearsals::in(previewSaying($data));
    $started = [];
    $leftOut = [];

    foreach ($rehearsal->wouldStart() as $service) {
        $started[] = $service->named();
    }

    foreach ($rehearsal->leftOut() as $profile) {
        $leftOut[] = sprintf('%s:%s', $profile->profile(), $profile->needs()->value);
    }

    return sprintf('%s / %s', implode(',', $started), implode(',', $leftOut));
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PreviewEnvelope', ['api_version' => 1, 'kind' => 'preview', 'data' => aRehearsalInFull()]))->toBe([]);
});

it('reads what would start and every profile left out, in order', function (): void {
    expect(theRehearsalRead(aRehearsalInFull()))->toBe('sabnzbd,sonarr / torrent:torrent,nzb:usenet');
});

it('reads a start that would bring nothing up and leave nothing out', function (): void {
    $rehearsal = Rehearsals::in(previewSaying([...aRehearsalInFull(), 'services' => [], 'dropped' => []]));

    expect($rehearsal->wouldStart()->isEmpty())->toBeTrue()
        ->and($rehearsal->leftOut())->toHaveCount(0);
});

it('refuses a payload that is not a table, or a list that is absent or not a list', function (mixed $data, string $field): void {
    expect(fn(): WhatStartingItWouldComeTo => Rehearsals::in(previewSaying($data)))
        ->toThrow(RehearsalIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['nothing', 'data'],
    [array_diff_key(aRehearsalInFull(), ['services' => true]), 'services'],
    [[...aRehearsalInFull(), 'services' => 'sonarr'], 'services'],
    [[...aRehearsalInFull(), 'services' => ['a' => 'sonarr']], 'services'],
    [array_diff_key(aRehearsalInFull(), ['dropped' => true]), 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => 'torrent'], 'dropped'],
]);

it('refuses an entry that is not what the contract says, naming its list and position', function (mixed $data, string $field): void {
    expect(fn(): WhatStartingItWouldComeTo => Rehearsals::in(previewSaying($data)))
        ->toThrow(RehearsalIsUnreadable::class, sprintf('Entry 1 of the preview\'s `%s`', $field));
})->with([
    [[...aRehearsalInFull(), 'services' => ['sabnzbd', ' ']], 'services'],
    [[...aRehearsalInFull(), 'services' => ['sabnzbd', 7]], 'services'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], 'nzb']], 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['needs' => 'usenet']]], 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['profile' => 'nzb']]], 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['profile' => ' ', 'needs' => 'usenet']]], 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['profile' => 'nzb', 'needs' => 'money']]], 'dropped'],
    [[...aRehearsalInFull(), 'dropped' => [['profile' => 'torrent', 'needs' => 'torrent'], ['profile' => 'nzb', 'needs' => 7]]], 'dropped'],
]);
