<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Forgotten;

it('says how many a store let go of', function (): void {
    expect(Forgotten::rows(3)->howMany())->toBe(3)
        ->and(Forgotten::nothing()->howMany())->toBe(0);
});

it('adds what two stores let go of', function (): void {
    expect(Forgotten::rows(3)->beside(Forgotten::rows(4))->howMany())->toBe(7)
        ->and(Forgotten::nothing()->beside(Forgotten::rows(2))->howMany())->toBe(2);
});
