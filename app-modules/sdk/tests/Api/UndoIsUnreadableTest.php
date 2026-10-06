<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Sdk\Api\TheRunPutBack;
use Modules\Sdk\Api\UndoIsUnreadable;
use Modules\Sdk\Api\WireField;
use Tests\Support\WhatTheContractAccepts;

it('names the field an undo report left out', function (): void {
    expect(UndoIsUnreadable::missing(WireField::Left)->getMessage())
        ->toBe('The undo envelope has no `left`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names the list, the row and the field of a row it could not read', function (): void {
    expect(UndoIsUnreadable::entry(WireField::Left, WireField::Because, 2)->getMessage())
        ->toBe('Row 2 of `left` in the undo envelope has no readable `because`. It is refused rather than dropped: a report one row short says something went back that did not.');
});

it('names every kind of reversal it reads when refusing one it does not', function (): void {
    expect(UndoIsUnreadable::does('teleport', 1)->getMessage())
        ->toBe('Row 1 of `reversed` in the undo envelope says it does `teleport`, and this app reads `remove`, `restore`, `delete`, `withdraw`, `rewind`, `repin`, `reconfigure`, `revoke`, `reinstate`.');
});

/**
 * What a stack says of a run put back, two rows to most lists, with a list changed where a case says.
 *
 * @param  array<mixed> $changed
 * @return array<string, mixed>
 */
function aRunPutBackOfTwoRows(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'undo', 'data' => [
        'reversed' => [
            ['target' => 'lemonfiber', 'action' => ['does' => 'restore', 'key' => 'LIBRARY_PATH', 'wrote' => '/srv/new']],
            ['target' => 'sonarr', 'action' => ['does' => 'delete', 'path' => '/srv/sonarr/extra']],
        ],
        'left' => [['target' => 'sonarr', 'because' => 'the service that made it did not answer'], ['target' => 'radarr', 'because' => 'it was not there']],
        'noted' => [['target' => 'lemonfiber', 'because' => 'the library stays where it was moved to'], ['target' => 'sonarr', 'because' => 'its settings were read again']],
        'rehearsed' => false,
        ...$changed,
    ]];
}

it('stands in for a stack with a report the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UndoEnvelope', aRunPutBackOfTwoRows()))->toBe([]);
});

it('names the row the reader stopped at, counted from nought in the stack\'s order', function (array $changed, string $said): void {
    expect(static fn(): ARunPutBack => TheRunPutBack::in(new Envelope(1, 'undo', aRunPutBackOfTwoRows($changed)['data'])))->toThrow(UndoIsUnreadable::class, $said);
})->with([
    [['reversed' => [['target' => 'lemonfiber', 'action' => ['does' => 'delete', 'path' => '/srv']], ['target' => ' ', 'action' => ['does' => 'delete', 'path' => '/srv']]]], 'Row 1 of `reversed` in the undo envelope has no readable `target`'],
    [['reversed' => [['target' => 'lemonfiber', 'action' => ['does' => 'delete', 'path' => '/srv']], ['target' => 'sonarr', 'action' => ['does' => 'teleport']]]], 'Row 1 of `reversed`'],
    [['left' => [['target' => 'sonarr', 'because' => 'it did not answer'], ['target' => 'radarr', 'because' => ' ']]], 'Row 1 of `left` in the undo envelope has no readable `because`'],
    [['noted' => [['target' => 'lemonfiber', 'because' => 'it moved'], ['target' => ' ', 'because' => 'it moved']]], 'Row 1 of `noted` in the undo envelope has no readable `target`'],
]);
