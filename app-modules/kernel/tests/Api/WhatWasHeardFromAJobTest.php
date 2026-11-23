<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\AJobSaidNothing;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

use function sprintf;

/** Which arm an answer about a job's lines takes, and what it carried there. */
function whichArmAJobsLinesTook(WhatWasHeardFromAJob $heard): string
{
    return $heard->either(
        said: static fn(string ...$lines): Code => Code::of(sprintf('said %s', implode(' | ', $lines))),
        nothingNew: static fn(): Code => Code::of('nothing new'),
        met: static fn(Obstacle $why): Code => Code::of(sprintf('met %s', $why->kind()->value)),
    )->shown();
}

it('says the lines heard in the order heard, nothing new where none were, and what stood in the way', function (): void {
    expect(whichArmAJobsLinesTook(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr')))->toBe('said Pulling sonarr | Starting sonarr')
        ->and(whichArmAJobsLinesTook(WhatWasHeardFromAJob::said()))->toBe('nothing new')
        ->and(whichArmAJobsLinesTook(WhatWasHeardFromAJob::nothingNew()))->toBe('nothing new')
        ->and(whichArmAJobsLinesTook(WhatWasHeardFromAJob::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a line that says nothing, rather than drawing a blank', function (): void {
    expect(fn(): WhatWasHeardFromAJob => WhatWasHeardFromAJob::said('Pulling sonarr', ''))
        ->toThrow(AJobSaidNothing::class, 'blank or not text');
});
