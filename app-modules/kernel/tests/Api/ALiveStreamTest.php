<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LiveStreamIsUnfit;
use Modules\Kernel\Api\MustNotLeaveThisProcess;

use function print_r;
use function serialize;
use function str_repeat;

/** A stream served both ways, its pass in each address. Named for this file. */
function aStreamWithItsPass(): ALiveStream
{
    return ALiveStream::opened(
        's1',
        'https://door.home/live/s1/stream.ts?lfpass=secret',
        'https://door.home/live/s1/main.m3u8?lfpass=secret',
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
        Instant::atEpochSeconds(1_000),
    );
}

it('carries both addresses, the door and the lapse, and is renewed to a later lapse at the same addresses', function (): void {
    $renewed = aStreamWithItsPass()->renewedUntil(Instant::atEpochSeconds(1_300));

    expect($renewed->stream())->toBe('s1')
        ->and($renewed->directForThePlayer())->toBe('https://door.home/live/s1/stream.ts?lfpass=secret')
        ->and($renewed->transcodedForThePlayer())->toBe('https://door.home/live/s1/main.m3u8?lfpass=secret')
        ->and($renewed->door()->forThePlayer())->toBe(str_repeat('c', Fingerprint::CHARACTERS))
        ->and($renewed->lapses()->epochSeconds())->toBe(1_300)
        ->and(aStreamWithItsPass()->lapses()->epochSeconds())->toBe(1_000);
});

it('says nothing of its pass where it is printed or encoded', function (): void {
    expect(print_r(aStreamWithItsPass(), return: true))->not->toContain('secret')
        ->and(aStreamWithItsPass()->__debugInfo())->toBe(['stream' => '(a live stream, hidden)'])
        ->and(json_encode(aStreamWithItsPass()))->toBe('"(a live stream, hidden)"');
});

it('refuses to be written down', function (): void {
    expect(static fn(): string => serialize(aStreamWithItsPass()))->toThrow(MustNotLeaveThisProcess::class);
});

it('refuses to be read back', function (): void {
    expect(static function (): void {
        aStreamWithItsPass()->__unserialize([]);
    })->toThrow(MustNotLeaveThisProcess::class);
});

it('refuses a stream with nothing to renew it by, or served nowhere', function (): void {
    $door = Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS));

    expect(static fn(): ALiveStream => ALiveStream::opened('', 'https://door.home/a.ts', '', $door, Instant::atEpochSeconds(1)))
        ->toThrow(LiveStreamIsUnfit::class, 'nothing to renew or stop it by')
        ->and(static fn(): ALiveStream => ALiveStream::opened('s1', '', '', $door, Instant::atEpochSeconds(1)))
        ->toThrow(LiveStreamIsUnfit::class, 'neither the tuner')
        ->and(ALiveStream::opened('s1', '', 'https://door.home/a.m3u8', $door, Instant::atEpochSeconds(1))->directForThePlayer())
        ->toBe('')
        ->and(ALiveStream::opened('s1', 'https://door.home/a.ts', '', $door, Instant::atEpochSeconds(1))->transcodedForThePlayer())
        ->toBe('');
});
