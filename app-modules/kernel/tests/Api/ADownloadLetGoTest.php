<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhetherItWasRehearsed;

it('carries which download, what it occupied and whether it was only rehearsed', function (): void {
    $gone = ADownloadLetGo::reported('Show.Season1', 4_000, WhetherItWasRehearsed::CarriedOut);
    $rehearsed = ADownloadLetGo::reported('Show.Season1', 0, WhetherItWasRehearsed::Rehearsed);

    expect([$gone->name(), $gone->bytes(), $gone->wasRehearsed()])->toBe(['Show.Season1', 4_000, WhetherItWasRehearsed::CarriedOut])
        ->and([$rehearsed->bytes(), $rehearsed->wasRehearsed()])->toBe([0, WhetherItWasRehearsed::Rehearsed]);
});

it('refuses a report naming nothing, or a size below nothing', function (): void {
    expect(fn(): ADownloadLetGo => ADownloadLetGo::reported(' ', 1, WhetherItWasRehearsed::CarriedOut))
        ->toThrow(RoomSaysNothing::class, '`name`')
        ->and(fn(): ADownloadLetGo => ADownloadLetGo::reported('Show.Season1', -1, WhetherItWasRehearsed::CarriedOut))
        ->toThrow(RoomSaysNothing::class, '`bytes`');
});
