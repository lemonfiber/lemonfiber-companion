<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Sdk\Api\ArchivesAreUnreadable;
use Modules\Sdk\Api\TheArchives;
use Tests\Support\WhatTheContractAccepts;

/**
 * An `archives` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function archivesSaying(mixed $data): Envelope
{
    return new Envelope(1, 'archives', $data);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ArchivesEnvelope', ['api_version' => 1, 'kind' => 'archives', 'data' => ['archives' => ['lemonfiber-20260924-0300-full']]]))->toBe([]);
});

it('reads each copy by its name, in the stack\'s order', function (): void {
    $copies = TheArchives::in(archivesSaying(['archives' => ['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full']]));

    expect(iterator_to_array($copies, preserve_keys: false))->toBe(['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full']);
});

it('reads a list that arrived as an object by position, not by key', function (): void {
    $copies = TheArchives::in(archivesSaying(['archives' => ['newest' => 'lemonfiber-20260924-0300-full']]));

    expect(iterator_to_array($copies, preserve_keys: false))->toBe(['lemonfiber-20260924-0300-full']);
});

it('N6-R9 — reads an empty list as no copy, which is an answer', function (): void {
    expect(TheArchives::in(archivesSaying(['archives' => []])))->toHaveCount(0);
});

it('N6-R9 — refuses a payload with no list, rather than reading it as empty', function (mixed $data): void {
    expect(fn(): mixed => TheArchives::in(archivesSaying($data)))->toThrow(ArchivesAreUnreadable::class, '`');
})->with([
    'no payload' => ['nothing'],
    'no list' => [[]],
    'not a list' => [['archives' => 'none']],
]);

it('N6-R9 — refuses a copy that is not a name, by its position, rather than dropping it', function (mixed $said): void {
    expect(fn(): mixed => TheArchives::in(archivesSaying(['archives' => ['lemonfiber-20260924-0300-full', $said]])))
        ->toThrow(ArchivesAreUnreadable::class, 'Archive 1 ');
})->with(['blank' => [' '], 'a number' => [7], 'nothing' => [null]]);
