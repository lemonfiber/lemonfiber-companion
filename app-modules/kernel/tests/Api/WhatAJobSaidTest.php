<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

use function range;
use function sprintf;

/**
 * As many numbered lines as asked, from the first named.
 *
 * @return list<string>
 */
function numberedLines(int $from, int $to): array
{
    return array_map(static fn(int $line): string => sprintf('Line %d', $line), range($from, $to));
}

it('has said nothing before a line is heard, and keeps nothing for an answer that carried no line', function (): void {
    $said = WhatAJobSaid::nothing()
        ->heard(WhatWasHeardFromAJob::nothingNew())
        ->heard(WhatWasHeardFromAJob::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($said->lines)->toBe([])
        ->and($said->letGo)->toBe(0);
});

it('keeps every line heard in the order heard, newest last', function (): void {
    $said = WhatAJobSaid::nothing()
        ->heard(WhatWasHeardFromAJob::said('Waiting for the database'))
        ->heard(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr'));

    expect($said->lines)->toBe(['Waiting for the database', 'Pulling sonarr', 'Starting sonarr'])
        ->and($said->letGo)->toBe(0);
});

it('keeps as many lines as a phone shows, letting go of the oldest and counting them', function (): void {
    $said = WhatAJobSaid::nothing()
        ->heard(WhatWasHeardFromAJob::said(...numberedLines(1, HowManyLines::ON_A_PHONE)))
        ->heard(WhatWasHeardFromAJob::said('Line after the bound', 'And one more'));

    expect($said->lines)->toHaveCount(HowManyLines::ON_A_PHONE)
        ->and($said->lines[0])->toBe('Line 3')
        ->and($said->lines[HowManyLines::ON_A_PHONE - 1])->toBe('And one more')
        ->and($said->letGo)->toBe(2)
        ->and($said->heard(WhatWasHeardFromAJob::said('Another'))->letGo)->toBe(3);
});

it('keeps every line up to the bound itself', function (): void {
    $said = WhatAJobSaid::nothing()->heard(WhatWasHeardFromAJob::said(...numberedLines(1, HowManyLines::ON_A_PHONE)));

    expect($said->lines)->toBe(numberedLines(1, HowManyLines::ON_A_PHONE))
        ->and($said->letGo)->toBe(0);
});
