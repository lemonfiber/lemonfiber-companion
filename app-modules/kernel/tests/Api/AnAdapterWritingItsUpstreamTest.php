<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAdapterWritingItsUpstream;
use Modules\Kernel\Api\PluginSaysNothing;

it('holds the adapter, the service whose files it writes, and where that service\'s directory lands inside it', function (): void {
    $written = AnAdapterWritingItsUpstream::by('homepage-adapter', 'homepage', '/upstream');

    expect([$written->adapter(), $written->upstream(), $written->at()])->toBe(['homepage-adapter', 'homepage', '/upstream']);
});

it('refuses a blank adapter, service or place', function (string $adapter, string $upstream, string $at): void {
    expect(static fn(): AnAdapterWritingItsUpstream => AnAdapterWritingItsUpstream::by($adapter, $upstream, $at))->toThrow(PluginSaysNothing::class);
})->with([
    'no adapter' => [' ', 'homepage', '/upstream'],
    'no service' => ['homepage-adapter', '', '/upstream'],
    'no place' => ['homepage-adapter', 'homepage', ' '],
]);
