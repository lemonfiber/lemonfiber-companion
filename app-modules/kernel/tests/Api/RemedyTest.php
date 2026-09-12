<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\RemedySaysNothing;

it('carries the action as the server phrased it', function (): void {
    expect(Remedy::of('Free 20 GB on the media drive')->action())
        ->toBe('Free 20 GB on the media drive');
});

it('trims the action before carrying it', function (): void {
    expect(Remedy::of("  Restart the stack \n")->action())->toBe('Restart the stack');
});

it('refuses a remedy that is empty', function (): void {
    expect(fn(): Remedy => Remedy::of(''))->toThrow(RemedySaysNothing::class);
});

it('refuses a remedy that is only whitespace', function (): void {
    expect(fn(): Remedy => Remedy::of('   '))->toThrow(RemedySaysNothing::class);
});

it('says what a blank remedy would have rendered as', function (): void {
    expect(fn(): Remedy => Remedy::of(''))
        ->toThrow(RemedySaysNothing::class, 'button with no label');
});
