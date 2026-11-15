<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\MustNotLeaveThisProcess;

use function print_r;
use function serialize;
use function sprintf;
use function str_repeat;
use function strlen;
use function unserialize;

it('is half a key nobody can guess, and reads back only at that length', function (): void {
    $secret = AWakingSecret::from(KeyMaterial::of(sprintf('%s%s', str_repeat('a', 16), str_repeat('b', 16))));

    expect($secret->bytes())->toBe(str_repeat('a', 16))
        ->and(AWakingSecret::length())->toBe(16)
        ->and(AWakingSecret::of(str_repeat('c', 16))->bytes())->toBe(str_repeat('c', 16))
        ->and(static fn(): AWakingSecret => AWakingSecret::of('short'))->toThrow(AWakeIsUnfit::class, 'is 16 bytes, and this one is 5');
});

it('is printed as nothing, and never serialised either way', function (): void {
    $secret = AWakingSecret::of(str_repeat('c', 16));

    expect(print_r($secret, return: true))->not->toContain('cccc')
        ->and(static fn(): string => serialize($secret))->toThrow(MustNotLeaveThisProcess::class)
        ->and(static fn(): mixed => unserialize(sprintf('O:%d:"%s":0:{}', strlen(AWakingSecret::class), AWakingSecret::class)))->toThrow(MustNotLeaveThisProcess::class);
});

it('takes a public key only as an uncompressed P-256 point', function (): void {
    $point = sprintf("\x04%s", str_repeat("\x01", 64));

    expect(AWakingKey::of($point)->bytes())->toBe($point)
        ->and(static fn(): AWakingKey => AWakingKey::of(sprintf("\x02%s", str_repeat("\x01", 64))))->toThrow(AWakeIsUnfit::class)
        ->and(static fn(): AWakingKey => AWakingKey::of("\x04short"))->toThrow(AWakeIsUnfit::class, 'this one is 6 bytes');
});
