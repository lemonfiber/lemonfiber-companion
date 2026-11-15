<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\WhyItemsStayed;

it('carries the room returned, how many items went and what was left behind', function (): void {
    $left = WhyItemsStayed::because('Permission denied');
    $back = TheRoomGivenBack::reported(30_000, 12, $left);

    expect([$back->bytes(), $back->went(), $back->left()])->toBe([30_000, 12, $left]);
});

it('refuses a figure below nothing', function (): void {
    expect(fn(): TheRoomGivenBack => TheRoomGivenBack::reported(-1, 0, WhyItemsStayed::because()))->toThrow(RoomSaysNothing::class, '`bytes`')
        ->and(fn(): TheRoomGivenBack => TheRoomGivenBack::reported(0, -1, WhyItemsStayed::because()))->toThrow(RoomSaysNothing::class, '`gone`');
});

it('reads nothing back and nothing gone as a report, not a refusal', function (): void {
    $back = TheRoomGivenBack::reported(0, 0, WhyItemsStayed::because());

    expect([$back->bytes(), $back->went()])->toBe([0, 0]);
});
