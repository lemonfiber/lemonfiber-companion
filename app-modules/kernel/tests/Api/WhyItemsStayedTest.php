<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhyItemsStayed;

it('carries each reason in the stack\'s order, as a list', function (): void {
    $left = WhyItemsStayed::because(...['first' => 'Permission denied', 'second' => 'Read-only file system']);

    expect(iterator_to_array($left, preserve_keys: false))->toBe(['Permission denied', 'Read-only file system'])
        ->and(array_keys(iterator_to_array($left, preserve_keys: true)))->toBe([0, 1])
        ->and(count($left))->toBe(2)
        ->and(count(WhyItemsStayed::because()))->toBe(0);
});

it('refuses a reason left blank', function (): void {
    expect(fn(): WhyItemsStayed => WhyItemsStayed::because('Permission denied', ' '))->toThrow(RoomSaysNothing::class, '`why`');
});
