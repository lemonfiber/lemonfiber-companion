<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhyNothingIsShown;

it('separates the refusal worth re-asking about from the ones that are not', function (): void {
    // The whole reason these are cases rather than one boolean. A screen that
    // offers "turn notifications on" is right in front of somebody who has not
    // granted permission, wrong in front of somebody whose stack was removed a
    // second ago, and wrong again in front of a device that would not show it
    // whatever anybody says — and "not shown" alone cannot tell them apart.
    expect(WhyNothingIsShown::NotificationsAreNotPermitted->mightBeWorthAsking())->toBeTrue()
        ->and(WhyNothingIsShown::TheStackIsGone->mightBeWorthAsking())->toBeFalse()
        ->and(WhyNothingIsShown::TheDeviceWouldNotShowIt->mightBeWorthAsking())->toBeFalse();
});

it('is three cases, and a fourth would owe an answer to that question', function (): void {
    // Pinned so that adding a case is a decision rather than an oversight:
    // `mightBeWorthAsking()` returns false for anything it does not name, which
    // is the safe default and also the silent one. This fails first.
    expect(WhyNothingIsShown::cases())->toBe([
        WhyNothingIsShown::NotificationsAreNotPermitted,
        WhyNothingIsShown::TheStackIsGone,
        WhyNothingIsShown::TheDeviceWouldNotShowIt,
    ]);
});
