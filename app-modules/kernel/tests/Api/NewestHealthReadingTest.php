<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestHealthReading;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

use function sprintf;

/** Which arm the newest reading answered on, and what it carried, as one word. */
function whatTheNewestReadingSaid(NewestHealthReading $newest): string
{
    return $newest->either(
        found: static fn(SealedPayload $payload, Shape $shape, Instant $readAt): Code
            => Code::of(sprintf('found:%s:%d:%d', $payload->forTheStore(), $shape->value, $readAt->epochSeconds())),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

it('hands the found arm the payload, the shape and the moment it was read', function (): void {
    expect(whatTheNewestReadingSaid(NewestHealthReading::found(
        SealedPayload::of('a-sealed-summary'),
        Shape::One,
        Instant::atEpochSeconds(1_790_000_000),
    )))->toBe('found:a-sealed-summary:1:1790000000');
});

it('answers none with nothing to hand over', function (): void {
    expect(whatTheNewestReadingSaid(NewestHealthReading::none()))->toBe('none');
});

it('answers a reading this build cannot read apart from both', function (): void {
    expect(whatTheNewestReadingSaid(NewestHealthReading::thatThisBuildCannotRead()))->toBe('unreadable');
});
