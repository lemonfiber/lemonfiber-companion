<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatCopiesWereFound;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheCopiesTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatCopiesWereFound(WhatCopiesWereFound $answer): string
{
    return $answer->either(
        copies: static fn(TheCopies $copies): WhichArmTheCopiesTook => new WhichArmTheCopiesTook(sprintf('copies:%d', count($copies))),
        met: static fn(Obstacle $why): WhichArmTheCopiesTook => new WhichArmTheCopiesTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('N6-R9 — an empty list of copies and one that could not be read take different arms', function (): void {
    expect(whatCopiesWereFound(WhatCopiesWereFound::copies(TheCopies::named())))->toBe('copies:0')
        ->and(whatCopiesWereFound(WhatCopiesWereFound::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
