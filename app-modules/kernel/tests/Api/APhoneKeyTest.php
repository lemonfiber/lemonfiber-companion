<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\KeySaysNothing;

it('refuses a phone key with a blank name, and keeps one as the stack wrote it', function (): void {
    expect(static fn(): APhoneKeysName => APhoneKeysName::of(' '))->toThrow(KeySaysNothing::class, 'name')
        ->and(APhoneKeysName::of('Galaxy A51 · 7f3a')->shown())->toBe('Galaxy A51 · 7f3a');
});

it('refuses a key or a phone key named by no path segment, which revoking it could not name', function (string $name): void {
    expect(static fn(): APhoneKeysName => APhoneKeysName::of($name))->toThrow(KeySaysNothing::class, 'name')
        ->and(static fn(): AKeysName => AKeysName::of($name))->toThrow(KeySaysNothing::class, 'name');
})->with([
    'a dot segment' => ['.'],
    'a step up' => ['..'],
]);
