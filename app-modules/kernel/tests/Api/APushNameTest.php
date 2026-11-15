<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\AWakeIsUnfit;

use function str_repeat;

it('is the name this phone\'s key carries, written as a stack names an endpoint', function (string $key, string $name): void {
    expect(APushName::forThePhoneKey(APhoneKeysName::of($key))->shown())->toBe($name);
})->with([
    'a model and four characters' => ['Galaxy A51 · 7f3a', 'galaxy-a51-7f3a'],
    'the four characters alone' => ['7f3a', '7f3a'],
    'what cannot begin a name' => ['· Pixel_8.', 'pixel_8'],
    'nothing that can be written' => ['· ·', 'phone'],
    'longer than a stack takes' => [str_repeat('a', 70), str_repeat('a', 64)],
]);

it('reads back a name a stack would take, and refuses one it would not', function (): void {
    expect(APushName::of('galaxy-a51-7f3a')->shown())->toBe('galaxy-a51-7f3a')
        ->and(static fn(): APushName => APushName::of('Galaxy A51'))->toThrow(AWakeIsUnfit::class)
        ->and(static fn(): APushName => APushName::of('-galaxy'))->toThrow(AWakeIsUnfit::class)
        ->and(static fn(): APushName => APushName::of(str_repeat('a', 65)))->toThrow(AWakeIsUnfit::class);
});
