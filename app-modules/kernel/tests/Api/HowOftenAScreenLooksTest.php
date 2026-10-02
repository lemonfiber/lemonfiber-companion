<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowOftenAScreenLooks;

it('declares each cadence once, in the milliseconds the platform counts and the seconds a wait is measured in', function (): void {
    $said = [];

    foreach (HowOftenAScreenLooks::cases() as $cadence) {
        $said[$cadence->value] = [$cadence->milliseconds(), $cadence->seconds()];
    }

    expect($said)->toBe([
        'while_work_runs' => [5_000, 5],
        'while_listening' => [2_000, 2],
        'after_a_break' => [10_000, 10],
        'while_it_moves' => [5_000, 5],
        'while_open' => [60_000, 60],
    ]);
});
