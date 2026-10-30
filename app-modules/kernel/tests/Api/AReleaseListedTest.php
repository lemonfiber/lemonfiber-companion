<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AReleaseListed;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatAReleaseDelivers;

it('holds the version a listed release is named by', function (): void {
    expect(AReleaseListed::versioned('2.4.0', WhatAReleaseDelivers::saidNothing())->version())->toBe('2.4.0');
});

it('refuses a listed release named by nothing', function (string $version): void {
    expect(static fn(): AReleaseListed => AReleaseListed::versioned($version, WhatAReleaseDelivers::saidNothing()))->toThrow(VersionIsBlank::class);
})->with([
    'nothing' => [''],
    'only spaces' => ['  '],
]);
