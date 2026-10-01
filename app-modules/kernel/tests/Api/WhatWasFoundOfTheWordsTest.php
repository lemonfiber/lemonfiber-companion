<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatWasFoundOfTheWords;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

it('takes the arm for what came back', function (): void {
    $fold = static fn(WhatWasFoundOfTheWords $answer): string => $answer->either(
        found: static fn(TheGlossary $words): TheWordCarriedOut => new TheWordCarriedOut(sprintf('found:%d', $words->count())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;

    expect($fold(WhatWasFoundOfTheWords::found(TheGlossary::of(AWord::explained('pin', 'Held at', '')))))->toBe('found:1')
        ->and($fold(WhatWasFoundOfTheWords::found(TheGlossary::of())))->toBe('found:0')
        ->and($fold(WhatWasFoundOfTheWords::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
