<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowFarTheRemovalReached;

it('is done only where it reached everywhere', function (): void {
    expect(HowFarTheRemovalReached::Everywhere->isDone())->toBeTrue()
        ->and(HowFarTheRemovalReached::MediaServerOnly->isDone())->toBeFalse()
        ->and(HowFarTheRemovalReached::Nothing->isDone())->toBeFalse();
});

it('says each reach in a sentence of its own', function (): void {
    expect(HowFarTheRemovalReached::Everywhere->saidOnTheScreen())->toBe('stacks.removal.revoked.everywhere')
        ->and(HowFarTheRemovalReached::MediaServerOnly->saidOnTheScreen())->toBe('stacks.removal.revoked.media-server-only')
        ->and(HowFarTheRemovalReached::Nothing->saidOnTheScreen())->toBe('stacks.removal.revoked.nothing');
});
