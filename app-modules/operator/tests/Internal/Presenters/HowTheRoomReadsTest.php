<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Operator\Internal\Presenters\HowTheRoomReads;

/**
 * Every way a machine can stand for room has the glyph an operator reads it by.
 *
 * Written out case by case rather than sampled, so a case added to the enum
 * without a glyph fails here by name as well as at the match.
 */
it('draws each way the machine can stand for room with its own tone', function (WhereTheRoomStands $stands, Tone $tone): void {
    $room = WhereTheRoomWent::measured(TheVolumes::of(), $stands, TheAccount::of(), TheDownloadsOnDisk::of(), halted: false);

    expect(new HowTheRoomReads()->this($room, Instant::atEpochSeconds(0))->tone)->toBe($tone->value);
})->with([
    'could not be read' => [WhereTheRoomStands::Unknown, Tone::Unknown],
    'plenty of room' => [WhereTheRoomStands::Ample, Tone::Fine],
    'less than is comfortable' => [WhereTheRoomStands::Advisory, Tone::Attention],
    'going to fill' => [WhereTheRoomStands::Warning, Tone::Attention],
    'nearly full' => [WhereTheRoomStands::Critical, Tone::Trouble],
    'full' => [WhereTheRoomStands::Exhausted, Tone::Trouble],
]);

it('draws a reading that did not come back as unknown, never as comfortable', function (): void {
    $presenter = new HowTheRoomReads();

    expect($presenter->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->tone)->toBe(Tone::Unknown->value)
        ->and($presenter->signedOut()->tone)->toBe(Tone::Unknown->value);
});
