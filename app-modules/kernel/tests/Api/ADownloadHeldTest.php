<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\RoomSaysNothing;

it('carries the name exactly as the account listed it', function (): void {
    expect(ADownloadHeld::named(' Show.Season1 ')->name())->toBe(' Show.Season1 ');
});

it('refuses a download named nothing', function (): void {
    expect(fn(): ADownloadHeld => ADownloadHeld::named(' '))->toThrow(RoomSaysNothing::class, '`download`');
});
