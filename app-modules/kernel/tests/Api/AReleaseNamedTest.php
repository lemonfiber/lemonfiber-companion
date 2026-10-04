<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\VersionIsBlank;

it('holds the version a release is named by', function (): void {
    expect(AReleaseNamed::versioned('2.4.0')->version())->toBe('2.4.0');
});

it('refuses a release named by nothing', function (): void {
    expect(static fn(): AReleaseNamed => AReleaseNamed::versioned(' '))->toThrow(VersionIsBlank::class);
});
