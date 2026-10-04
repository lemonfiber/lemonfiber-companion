<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\RoomSaysNothing;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an amount takes, and what it carried there. */
function whatTheAmountSays(AnAmountOfRoom $amount): string
{
    return $amount->either(
        known: static fn(int $bytes): TheWordCarriedOut => new TheWordCarriedOut(sprintf('known:%d', $bytes)),
        unread: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unread'),
    )->said;
}

it('a figure that was not read is never nought', function (): void {
    expect(whatTheAmountSays(AnAmountOfRoom::of(0, 'free')))->toBe('known:0')
        ->and(whatTheAmountSays(AnAmountOfRoom::of(7, 'free')))->toBe('known:7')
        ->and(whatTheAmountSays(AnAmountOfRoom::unread()))->toBe('unread');
});

it('refuses a figure below nothing, naming it', function (): void {
    expect(fn(): AnAmountOfRoom => AnAmountOfRoom::of(-1, 'free'))->toThrow(RoomSaysNothing::class, '`free` as -1');
});
