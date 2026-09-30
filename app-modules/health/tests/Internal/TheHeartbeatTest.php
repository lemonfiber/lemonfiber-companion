<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Internal;

use function expect;
use function it;

use Modules\Health\Internal\TheHeartbeat;

it('beats every fifteen seconds, as the contract states', function (): void {
    expect(TheHeartbeat::interval()->inSeconds())->toBe(15);
});

it('lets a stream be silent for two heartbeats and no longer', function (): void {
    expect(TheHeartbeat::silenceAllowed()->inSeconds())->toBe(30);
});
