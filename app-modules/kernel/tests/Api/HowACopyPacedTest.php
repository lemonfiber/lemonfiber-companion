<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\KeepingSaysNothing;

it('carries the stack\'s figures and its verdict as they were given', function (): void {
    $brisk = HowACopyPaced::measured(moved: 1_200, budget: 629_145_600, brisk: true);
    $slow = HowACopyPaced::measured(moved: 900_000_000, budget: 629_145_600, brisk: false);

    expect($brisk->moved())->toBe(1_200)
        ->and($brisk->budget())->toBe(629_145_600)
        ->and($brisk->isBrisk())->toBeTrue()
        ->and($slow->moved())->toBe(900_000_000)
        ->and($slow->isBrisk())->toBeFalse();
});

it('takes nothing moved and nothing budgeted as figures, not as gaps', function (): void {
    $nothing = HowACopyPaced::measured(moved: 0, budget: 0, brisk: true);

    expect($nothing->moved())->toBe(0)
        ->and($nothing->budget())->toBe(0);
});

it('refuses a figure below nothing, naming it', function (int $moved, int $budget, string $named): void {
    expect(fn(): HowACopyPaced => HowACopyPaced::measured(moved: $moved, budget: $budget, brisk: true))
        ->toThrow(KeepingSaysNothing::class, $named);
})->with([
    'moved' => [-1, 10, '`moved` at -1'],
    'budget' => [10, -1, '`budget` at -1'],
]);
