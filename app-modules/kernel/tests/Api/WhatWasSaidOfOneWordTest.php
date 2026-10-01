<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatWasSaidOfOneWord;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer takes, folded to one line. */
function whichArmOneWordTakes(WhatWasSaidOfOneWord $answer): string
{
    return $answer->either(
        explained: static fn(AWord $word): TheWordCarriedOut => new TheWordCarriedOut(sprintf('explained:%s', $word->word())),
        unexplained: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unexplained'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for what came back', function (): void {
    expect(whichArmOneWordTakes(WhatWasSaidOfOneWord::explained(AWord::explained('seed', 'Sharing a finished download', ''))))->toBe('explained:seed')
        ->and(whichArmOneWordTakes(WhatWasSaidOfOneWord::unexplained()))->toBe('unexplained')
        ->and(whichArmOneWordTakes(WhatWasSaidOfOneWord::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
