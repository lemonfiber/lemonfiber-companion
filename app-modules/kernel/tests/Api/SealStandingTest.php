<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\SealStanding;

use function sprintf;

it('stands where the worse of two keys stands, whichever order they are asked in', function (): void {
    // Every pair, both ways round: a rule that looked only at the first key
    // would pass every row where the first was the worse one.
    $pairs = [
        [SealStanding::Held, SealStanding::Held, SealStanding::Held],
        [SealStanding::Held, SealStanding::MadeAfresh, SealStanding::MadeAfresh],
        [SealStanding::MadeAfresh, SealStanding::Held, SealStanding::MadeAfresh],
        [SealStanding::MadeAfresh, SealStanding::MadeAfresh, SealStanding::MadeAfresh],
        [SealStanding::Held, SealStanding::Unavailable, SealStanding::Unavailable],
        [SealStanding::Unavailable, SealStanding::Held, SealStanding::Unavailable],
        [SealStanding::MadeAfresh, SealStanding::Unavailable, SealStanding::Unavailable],
        [SealStanding::Unavailable, SealStanding::MadeAfresh, SealStanding::Unavailable],
        [SealStanding::Unavailable, SealStanding::Unavailable, SealStanding::Unavailable],
    ];

    foreach ($pairs as [$one, $other, $together]) {
        expect($one->beside($other))->toBe($together, sprintf('%s beside %s', $one->name, $other->name));
    }
});
