<?php

declare(strict_types=1);

namespace Modules\Device\Tests\Api;

use function expect;
use function it;

use Modules\Device\Api\WhatTheDeviceSaid;
use Modules\Kernel\Api\Asked;

it('reads every word the platform documents', function (): void {
    // The five are `PushNotifications::checkPermission()`'s own, and a word
    // this enum does not know is read as `NotDetermined` — so a misspelling
    // here is silent, and looks exactly like an operator who was never asked.
    // Written out so that the day a sixth arrives, this says which of them is
    // already handled.
    expect(WhatTheDeviceSaid::cases())->toBe([
        WhatTheDeviceSaid::Granted,
        WhatTheDeviceSaid::Denied,
        WhatTheDeviceSaid::NotDetermined,
        WhatTheDeviceSaid::Provisional,
        WhatTheDeviceSaid::Ephemeral,
    ]);
});

it('turns five platform words into the three this app reasons in', function (): void {
    // `provisional` and `ephemeral` are grants for the only question asked of
    // them — may a notification be shown — and this is the one place that is
    // decided. A call site deciding it again would eventually decide it
    // differently.
    expect(WhatTheDeviceSaid::Granted->means())->toBe(Asked::Granted)
        ->and(WhatTheDeviceSaid::Provisional->means())->toBe(Asked::Granted)
        ->and(WhatTheDeviceSaid::Ephemeral->means())->toBe(Asked::Granted)
        ->and(WhatTheDeviceSaid::Denied->means())->toBe(Asked::Declined)
        ->and(WhatTheDeviceSaid::NotDetermined->means())->toBe(Asked::NotYet);
});

it('reads nothing at all as nobody having been asked', function (): void {
    // What a bridge with no device answers, which is every machine that is not
    // a handset. `NotYet` is the safe reading in both directions: it withholds
    // the notification, and it leaves asking possible rather than recording a
    // refusal nobody made.
    expect(WhatTheDeviceSaid::orNothingSaid(null))->toBe(WhatTheDeviceSaid::NotDetermined)
        ->and(WhatTheDeviceSaid::orNothingSaid(null)->means())->toBe(Asked::NotYet);
});

it('reads a word it does not know the same way', function (): void {
    // A platform that grew a sixth case. Not an error: the app cannot know what
    // it means, and guessing in the permissive direction would show a
    // notification on a device whose operator may have refused one.
    expect(WhatTheDeviceSaid::orNothingSaid('something_new'))->toBe(WhatTheDeviceSaid::NotDetermined)
        ->and(WhatTheDeviceSaid::orNothingSaid(''))->toBe(WhatTheDeviceSaid::NotDetermined);
});

it('reads each word the platform actually sends', function (): void {
    expect(WhatTheDeviceSaid::orNothingSaid('granted'))->toBe(WhatTheDeviceSaid::Granted)
        ->and(WhatTheDeviceSaid::orNothingSaid('denied'))->toBe(WhatTheDeviceSaid::Denied)
        ->and(WhatTheDeviceSaid::orNothingSaid('not_determined'))->toBe(WhatTheDeviceSaid::NotDetermined)
        ->and(WhatTheDeviceSaid::orNothingSaid('provisional'))->toBe(WhatTheDeviceSaid::Provisional)
        ->and(WhatTheDeviceSaid::orNothingSaid('ephemeral'))->toBe(WhatTheDeviceSaid::Ephemeral);
});
