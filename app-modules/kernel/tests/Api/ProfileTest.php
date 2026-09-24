<?php

declare(strict_types=1);

use Modules\Kernel\Api\Profile;
use Modules\Kernel\Api\ProfileIsUnnamed;

it('N18-R7 — carries the name exactly as the stack spells it', function (): void {
    expect(Profile::called('media')->named())->toBe('media');
});

it('keeps the name, less the whitespace around it', function (): void {
    expect(Profile::called("  torrent\n")->named())->toBe('torrent');
});

it('refuses a profile named as nothing at all', function (): void {
    expect(fn(): Profile => Profile::called('   '))
        ->toThrow(ProfileIsUnnamed::class, 'nothing at all');
});
