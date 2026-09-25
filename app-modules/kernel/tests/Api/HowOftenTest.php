<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowOften;

it('states each cadence once, in the milliseconds the platform counts and the seconds a sentence says', function (): void {
    $said = [];

    foreach (HowOften::cases() as $cadence) {
        $said[$cadence->value] = [$cadence->milliseconds(), $cadence->seconds(), $cadence->saidOnTheScreen()];
    }

    expect($said)->toBe([
        'while_work_runs' => [5_000, 5, 'health.every.while_work_runs'],
        'while_listening' => [2_000, 2, 'health.every.while_listening'],
        'after_a_break' => [10_000, 10, 'health.every.after_a_break'],
    ]);
});
