<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Operator\Internal\Presenters\HowThisCopyReads;

/**
 * Every way the running copy can stand against what is released has the glyph
 * an operator reads it by.
 *
 * Written out case by case rather than sampled, so a case added to the enum
 * without a glyph fails here by name as well as at the match.
 */
it('draws each way the running copy can stand with its own tone', function (WhereThisCopyStands $stands, Tone $tone): void {
    $copy = ThisCopyOfLemonfiber::reported(
        '0.15.0',
        HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Installer, ''),
        $stands,
        WhatIsReleased::nothing(),
        '',
        HowItWouldBeUpdated::byRunning('lemonfiber update self'),
        WhatAnUpdateWouldBring::said('The program', 'Your settings are left alone'),
    );

    expect(new HowThisCopyReads()->this($copy)->tone)->toBe($tone->value);
})->with([
    'the newest' => [WhereThisCopyStands::Current, Tone::Fine],
    'updated by another tool' => [WhereThisCopyStands::ManagedExternally, Tone::Fine],
    'a newer one is out' => [WhereThisCopyStands::UpdateAvailable, Tone::Attention],
    'could not be checked' => [WhereThisCopyStands::CheckFailed, Tone::Unknown],
]);

it('draws a reading that did not come back as unknown, never as current', function (): void {
    $presenter = new HowThisCopyReads();

    expect($presenter->met(Obstacle::StackDidNotAnswer)->tone)->toBe(Tone::Unknown->value)
        ->and($presenter->signedOut()->tone)->toBe(Tone::Unknown->value);
});
