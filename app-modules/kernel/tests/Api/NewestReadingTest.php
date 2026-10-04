<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

use function sprintf;

/** Which arm the newest reading answered on, and what it carried, as one word. */
function whatTheNewestReadingSaid(NewestReading $newest): string
{
    return $newest->either(
        found: static fn(SealedPayload $payload, Shape $shape, Instant $readAt): Code
            => Code::of(sprintf('found:%s:%d:%d', $payload->forTheStore(), $shape->value, $readAt->epochSeconds())),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

/** A reading found, sealed as a store hands it back. */
function aReadingFound(): NewestReading
{
    return NewestReading::found(SealedPayload::of('a-sealed-reading'), Shape::One, Instant::atEpochSeconds(1_790_000_000));
}

it('hands the found arm the payload, the shape and the moment it was read', function (): void {
    expect(whatTheNewestReadingSaid(aReadingFound()))->toBe('found:a-sealed-reading:1:1790000000');
});

it('answers none with nothing to hand over', function (): void {
    expect(whatTheNewestReadingSaid(NewestReading::none()))->toBe('none');
});

it('answers a reading this build cannot read apart from both', function (): void {
    expect(whatTheNewestReadingSaid(NewestReading::thatThisBuildCannotRead()))->toBe('unreadable');
});

it('holds a row where a reading is found and where one cannot be read, and none where nothing is kept', function (): void {
    expect(aReadingFound()->holdsARow())->toBeTrue()
        ->and(NewestReading::thatThisBuildCannotRead()->holdsARow())->toBeTrue()
        ->and(NewestReading::none()->holdsARow())->toBeFalse();
});
