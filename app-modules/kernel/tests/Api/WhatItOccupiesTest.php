<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhatItOccupies;

it('keeps both figures, and says they differ only where they do', function (): void {
    $shared = WhatItOccupies::counted(20, 15);
    $alone = WhatItOccupies::counted(15, 15);

    expect([$shared->logical(), $shared->physical(), $shared->differs()])->toBe([20, 15, true])
        ->and($alone->differs())->toBeFalse();
});

it('refuses either figure below nothing, naming it, and takes nought', function (): void {
    expect(fn(): WhatItOccupies => WhatItOccupies::counted(-1, 0))->toThrow(RoomSaysNothing::class, '`logical`')
        ->and(fn(): WhatItOccupies => WhatItOccupies::counted(0, -1))->toThrow(RoomSaysNothing::class, '`physical`')
        ->and(WhatItOccupies::counted(0, 0)->physical())->toBe(0);
});
