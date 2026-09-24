<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatWasMeasured;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheMeasuringTook
{
    public function __construct(public string $said) {}
}

it('N12-R10 — a machine that could not be asked is never one with room to spare', function (): void {
    $fold = static fn(WhatWasMeasured $answer): string => $answer->either(
        measured: static fn(WhereTheRoomWent $room): WhichArmTheMeasuringTook => new WhichArmTheMeasuringTook(sprintf('measured:%s', $room->stands()->value)),
        met: static fn(Obstacle $why): WhichArmTheMeasuringTook => new WhichArmTheMeasuringTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasMeasured::measured(WhereTheRoomWent::measured(TheVolumes::of(), WhereTheRoomStands::Ample, TheAccount::of(), TheDownloadsOnDisk::of(), halted: false))))->toBe('measured:ample')
        ->and($fold(WhatWasMeasured::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
