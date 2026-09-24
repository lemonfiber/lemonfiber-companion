<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatWasFoundOfTheWords;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheGlossaryTook
{
    public function __construct(public string $said) {}
}

it('takes the arm for what came back', function (): void {
    $fold = static fn(WhatWasFoundOfTheWords $answer): string => $answer->either(
        found: static fn(TheGlossary $words): WhichArmTheGlossaryTook => new WhichArmTheGlossaryTook(sprintf('found:%d', $words->count())),
        met: static fn(Obstacle $why): WhichArmTheGlossaryTook => new WhichArmTheGlossaryTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundOfTheWords::found(TheGlossary::of(AWord::explained('pin', 'Held at', '')))))->toBe('found:1')
        ->and($fold(WhatWasFoundOfTheWords::found(TheGlossary::of())))->toBe('found:0')
        ->and($fold(WhatWasFoundOfTheWords::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
