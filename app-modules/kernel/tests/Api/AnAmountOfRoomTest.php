<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\RoomSaysNothing;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheAmountTook
{
    public function __construct(public string $said) {}
}

/** Which arm an amount takes, and what it carried there. */
function whatTheAmountSays(AnAmountOfRoom $amount): string
{
    return $amount->either(
        known: static fn(int $bytes): WhichArmTheAmountTook => new WhichArmTheAmountTook(sprintf('known:%d', $bytes)),
        unread: static fn(): WhichArmTheAmountTook => new WhichArmTheAmountTook('unread'),
    )->said;
}

it('N12-R10 — a figure that was not read is never nought', function (): void {
    expect(whatTheAmountSays(AnAmountOfRoom::of(0, 'free')))->toBe('known:0')
        ->and(whatTheAmountSays(AnAmountOfRoom::of(7, 'free')))->toBe('known:7')
        ->and(whatTheAmountSays(AnAmountOfRoom::unread()))->toBe('unread');
});

it('refuses a figure below nothing, naming it', function (): void {
    expect(fn(): AnAmountOfRoom => AnAmountOfRoom::of(-1, 'free'))->toThrow(RoomSaysNothing::class, '`free` as -1');
});
