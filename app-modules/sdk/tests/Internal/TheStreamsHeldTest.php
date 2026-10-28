<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function afterEach;

use Closure;

use function expect;
use function it;

use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Internal\TheStreamsHeld;
use Saloon\Http\Faking\MockClient;
use Tests\Support\AStreamThatStaysOpen;
use Tests\Support\Fakes\WhereHearingLeftOffInMemory;
use Tests\Support\TheStreamListenedTo;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * Each take of this stream held for one stack, saying whether the stream had ended.
 *
 * @return Closure(): bool
 */
function takesOf(AStreamThatStaysOpen $stream, WhereHearingLeftOffInMemory $kept): Closure
{
    MockClient::destroyGlobal();
    MockClient::global([$stream]);
    $streams = new TheStreamsHeld(new PinnedClients(), $kept, Listeners::NO_LONGER_THAN_MS);

    return static fn(): bool => $streams->taken(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession())->ended;
}

it('asks a store that refused to note where the stream got to again on the next take of the same stream', function (): void {
    $refusing = WhereHearingLeftOffInMemory::refusing();
    $take = takesOf(AStreamThatStaysOpen::saying(TheStreamListenedTo::anEvent('dashboard', '{}')), $refusing);

    expect([$take(), $take()])->toBe([false, false])
        ->and($refusing->timesNoted())->toBe(2);
});

it('asks a store that kept where the stream got to nothing more until the stream moves on', function (): void {
    $kept = WhereHearingLeftOffInMemory::nowhere();
    $stream = AStreamThatStaysOpen::saying(TheStreamListenedTo::anEvent('dashboard', '{}'));
    $take = takesOf($stream, $kept);

    $take();
    $take();
    $afterTwoTakes = $kept->timesNoted();
    $stream->says("id: run-2\nevent: dashboard\ndata: {}\n\n");
    $take();
    $take();

    expect($afterTwoTakes)->toBe(1)
        ->and($kept->timesNoted())->toBe(2);
});
