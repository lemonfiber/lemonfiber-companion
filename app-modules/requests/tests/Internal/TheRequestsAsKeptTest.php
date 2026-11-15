<?php

declare(strict_types=1);

namespace Modules\Requests\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Unsealed;
use Modules\Requests\Internal\TheRequestsAsKept;

use function sprintf;

/** A kept reading of one request for a film, standing as written. */
function aReadingStanding(string $standing): Unsealed
{
    return Unsealed::of(sprintf('{"requests":[{"id":"1","by":"sam","for":"A film","size":{},"standing":[%s],"refused":[]}]}', $standing));
}

it('reads a request standing as the stack said it stood', function (): void {
    expect(TheRequestsAsKept::read(Shape::One, aReadingStanding('"declined"')))->toBeInstanceOf(Requested::class);
});

it('reads nothing out of a request kept by a number, as a reading kept before requests were named by reference was', function (): void {
    expect(TheRequestsAsKept::read(Shape::One, Unsealed::of('{"requests":[{"number":1,"by":"sam","for":"A film","size":{},"standing":["declined"],"refused":[]}]}')))->toBeNull();
});

it('reads nothing out of a request whose standing is not one the stack says', function (string $standing): void {
    expect(TheRequestsAsKept::read(Shape::One, aReadingStanding($standing)))->toBeNull();
})->with([
    'a standing that is not text' => ['7'],
    'a standing no stack says' => ['"lost"'],
]);

/** A kept reading of one request the stack turned down, its moment kept as written. */
function aReadingTurnedDownAt(string $at): Unsealed
{
    return Unsealed::of(sprintf('{"requests":[{"id":"1","by":"sam","for":"A film","size":{},"standing":["declined"],"refused":[{"reason":"No room","at":[%s]}]}]}', $at));
}

/** When the one kept request was turned down, in whole seconds, or -1 where the moment does not read. */
function whenItWasTurnedDown(?Requested $kept): int
{
    foreach ($kept ?? Requested::none() as $wanted) {
        return $wanted->refusal(
            was: static fn(TurnedDown $why): Instant => $why->when()->read(
                read: static fn(Instant $at): Instant => $at,
                unreadable: static fn(): Instant => Instant::atEpochSeconds(0),
            ),
            wasNot: static fn(): Instant => Instant::atEpochSeconds(0),
        )->epochSeconds();
    }

    return -1;
}

it('reads when a kept request was turned down, as whole seconds or as the older written moment', function (string $at, int $seconds): void {
    expect(whenItWasTurnedDown(TheRequestsAsKept::read(Shape::One, aReadingTurnedDownAt($at))))->toBe($seconds);
})->with([
    'whole seconds' => ['1790150000', 1_790_150_000],
    'an older written moment' => ['"2026-09-23T14:13:20Z"', 1_790_172_800],
]);

it('reads nothing out of a request turned down at a moment that is neither seconds nor written', function (): void {
    expect(TheRequestsAsKept::read(Shape::One, aReadingTurnedDownAt('true')))->toBeNull();
});
