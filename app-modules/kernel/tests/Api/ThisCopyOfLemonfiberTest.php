<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\ItselfSaysNothing;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhereThisCopyStands;

/** A copy with the optional sentences given here. */
function aCopySaying(string $running, string $untold): ThisCopyOfLemonfiber
{
    return ThisCopyOfLemonfiber::reported(
        $running,
        HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Cargo, ''),
        WhereThisCopyStands::Current,
        WhatIsReleased::nothing(),
        $untold,
        HowItWouldBeUpdated::notSaid(),
        WhatAnUpdateWouldBring::said('The program', 'Settings are kept'),
    );
}

it('keeps everything it was reported with', function (): void {
    $gotThere = HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Cargo, 'cargo');
    $by = HowItWouldBeUpdated::byRunning('cargo install lemonfiber');
    $brings = WhatAnUpdateWouldBring::said('The program', 'Settings are kept');
    $released = WhatIsReleased::said('0.16.0', '- Plugins');
    $copy = ThisCopyOfLemonfiber::reported('0.15.0', $gotThere, WhereThisCopyStands::UpdateAvailable, $released, 'Checked an hour ago', $by, $brings);

    expect([$copy->running(), $copy->gotThere(), $copy->stands(), $copy->released(), $copy->untold(), $copy->updatedBy(), $copy->brings()])
        ->toBe(['0.15.0', $gotThere, WhereThisCopyStands::UpdateAvailable, $released, 'Checked an hour ago', $by, $brings]);
});

it('takes the reason availability could not be told empty', function (): void {
    expect(aCopySaying('0.15.0', '')->untold())->toBe('');
});

it('refuses a blank version, and a reason that is blank rather than empty', function (): void {
    expect(fn(): ThisCopyOfLemonfiber => aCopySaying(' ', ''))->toThrow(ItselfSaysNothing::class, '`running`')
        ->and(fn(): ThisCopyOfLemonfiber => aCopySaying('0.15.0', "\t"))->toThrow(ItselfSaysNothing::class, '`untold`');
});
