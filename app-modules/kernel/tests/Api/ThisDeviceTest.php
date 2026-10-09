<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\DeviceIdIsUnfit;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ThisDevice;

use function str_repeat;

it('carries an id the core accepts as written', function (): void {
    expect(ThisDevice::named('Device-1')->shown())->toBe('Device-1')
        ->and(ThisDevice::named(str_repeat('a', 64))->shown())->toBe(str_repeat('a', 64));
});

it('refuses an id the core would not accept', function (string $id): void {
    expect(static fn(): ThisDevice => ThisDevice::named($id))->toThrow(DeviceIdIsUnfit::class);
})->with([
    'too short' => ['Device1'],
    'too long' => [str_repeat('a', 65)],
    'a space' => ['this device'],
    'an underscore' => ['this_device'],
    'a letter outside ASCII' => ['dëvice-one'],
    'a line break after it' => ["this-device\n"],
]);

it('draws an id the core accepts from entropy of any length, the same id from the same nonce', function (): void {
    $short = ThisDevice::drawnFrom(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
    $long = ThisDevice::drawnFrom(Nonce::of(str_repeat('b', 200)));

    expect($short->shown())->toMatch('/\A[0-9a-f]{32}\z/')
        ->and($long->shown())->toMatch('/\A[0-9a-f]{32}\z/')
        ->and($short->shown())->not->toBe($long->shown())
        ->and(ThisDevice::drawnFrom(Nonce::of(str_repeat('a', Nonce::SHORTEST)))->shown())->toBe($short->shown());
});
