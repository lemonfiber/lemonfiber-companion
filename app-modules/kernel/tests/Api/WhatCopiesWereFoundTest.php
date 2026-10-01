<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatCopiesWereFound;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer takes, and what it carried there. */
function whatCopiesWereFound(WhatCopiesWereFound $answer): string
{
    return $answer->either(
        copies: static fn(TheCopies $copies): TheWordCarriedOut => new TheWordCarriedOut(sprintf('copies:%d', count($copies))),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('N6-R9 — an empty list of copies and one that could not be read take different arms', function (): void {
    expect(whatCopiesWereFound(WhatCopiesWereFound::copies(TheCopies::named())))->toBe('copies:0')
        ->and(whatCopiesWereFound(WhatCopiesWereFound::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
