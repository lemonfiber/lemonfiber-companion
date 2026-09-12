<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Health\Api\Category;

it('reads the nine the server recognises', function (): void {
    expect(array_map(
        static fn(Category $category): string => $category->value,
        Category::cases(),
    ))->toBe([
        'environment',
        'storage',
        'network',
        'vpn',
        'credentials',
        'services',
        'providers',
        'queue',
        'config',
    ]);
});
