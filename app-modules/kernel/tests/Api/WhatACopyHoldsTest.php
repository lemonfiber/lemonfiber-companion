<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhatACopyHolds;

it('keeps each thing a copy holds, in the order given', function (): void {
    $held = WhatACopyHolds::these('lemonfiber configuration', 'service configuration');

    expect(iterator_to_array($held, preserve_keys: false))->toBe(['lemonfiber configuration', 'service configuration'])
        ->and($held)->toHaveCount(2);
});

it('holds nothing where it was handed nothing', function (): void {
    expect(WhatACopyHolds::these())->toHaveCount(0);
});

it('refuses a thing that says nothing, wherever it is in the list', function (string ...$held): void {
    expect(fn(): WhatACopyHolds => WhatACopyHolds::these(...$held))->toThrow(KeepingSaysNothing::class, '`label`');
})->with([
    'first' => [' ', 'service configuration'],
    'later' => ['lemonfiber configuration', "\t"],
]);

it('is a list however it was handed its things', function (): void {
    expect(array_keys(iterator_to_array(WhatACopyHolds::these(...['one' => 'lemonfiber configuration', 'two' => 'materialised stack']), preserve_keys: true)))->toBe([0, 1]);
});
