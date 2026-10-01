<?php

declare(strict_types=1);

use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\Screens\AskingForHelpHere;
use Modules\Operator\Internal\Screens\AskingSomebodyIn;
use Modules\Operator\Internal\Screens\ConnectingADeviceForThem;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowTheServicesAreWired;
use Modules\Operator\Internal\Screens\LettingADownloadGo;
use Modules\Operator\Internal\Screens\PairingAPhone;
use Modules\Operator\Internal\Screens\PuttingACopyBack;
use Modules\Operator\Internal\Screens\PuttingThatRunBack;
use Modules\Operator\Internal\Screens\PuttingTheConfigurationBack;
use Modules\Operator\Internal\Screens\TakingACopyHere;
use Modules\Operator\Internal\Screens\TakingItOffThisMachine;
use Modules\Operator\Internal\Screens\TakingSomebodyOut;
use Modules\Operator\Internal\Screens\WatchingOneArrive;
use Modules\Operator\Internal\Screens\WhatIsAlreadyOnThisMachine;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Fakes\AScreenUnderTheLock;

// The AwaitsAnOutcome contract, run against every screen that follows work it
// sent and against the fake the lock's own tests stand on.
//
// What they all promise is the half the lock depends on most: a screen that has
// sent nothing awaits nothing, so the device may ask by itself over it. The
// other half — a screen still following what it sent says so — is each
// screen's own cadence, held by the screen's own tests.

/** Every screen that follows work it sent. */
const EVERY_SCREEN_FOLLOWING_WORK = [
    AskingForHelpHere::class,
    AskingSomebodyIn::class,
    ConnectingADeviceForThem::class,
    HowCurrentThisStackIs::class,
    HowTheServicesAreWired::class,
    LettingADownloadGo::class,
    PairingAPhone::class,
    PuttingACopyBack::class,
    PuttingThatRunBack::class,
    PuttingTheConfigurationBack::class,
    TakingACopyHere::class,
    TakingItOffThisMachine::class,
    TakingSomebodyOut::class,
    WatchingOneArrive::class,
    WhatIsAlreadyOnThisMachine::class,
    WhatToDoWithThis::class,
    WhatWouldBePutRight::class,
];

/** One of them, built as the router builds it, about the stack the stand-in holds. */
function aScreenThatSentNothing(string $class): AwaitsAnOutcome
{
    $screen = app()->make($class);

    if (! $screen instanceof NativeComponent || ! $screen instanceof AwaitsAnOutcome) {
        throw new RuntimeException(sprintf('%s is not a screen following work.', $class));
    }

    foreach (app()->make(Stacks::class)->configured() as $stack) {
        $screen->setParams(['stack' => $stack->id()->stored(), 'service' => 'gluetun']);
    }

    return $screen;
}

it('awaits nothing where nothing was sent', function (): void {
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    foreach (EVERY_SCREEN_FOLLOWING_WORK as $class) {
        expect(aScreenThatSentNothing($class)->awaitsAnOutcome())->toBeFalse($class);
    }

    expect(AScreenUnderTheLock::atRest()->awaitsAnOutcome())->toBeFalse();
});

it('says so where what it sent is still being carried out', function (): void {
    expect(AScreenUnderTheLock::awaitingAnOutcome()->awaitsAnOutcome())->toBeTrue();
});
