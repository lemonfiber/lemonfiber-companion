<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADeviceToWatchOn;
use Modules\Kernel\Api\APossibleCause;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingThatGoesWrong;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheDevices;
use Modules\Kernel\Api\TheTroubles;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlaybackMayStruggle;
use Modules\Operator\Internal\Screens\WhichAppToWatchOn;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatAdvises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Which app the household should watch on, device by device.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseAdviceIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Advice with a device of every rating, a symptom, and something straining playback. */
function adviceForEveryDevice(): WhatToWatchOn
{
    return WhatToWatchOn::advised(
        TheDevices::of(
            ADeviceToWatchOn::rated('An iPhone', 'Jellyfin for iOS', HowWellADeviceIsServed::Good, '', ''),
            ADeviceToWatchOn::rated('A Fire TV', 'Jellyfin for Fire TV', HowWellADeviceIsServed::Workable, 'Surround sound needs a setting changed', ''),
            ADeviceToWatchOn::rated('An older smart TV', 'The TV browser', HowWellADeviceIsServed::Poor, '', 'A streaming stick'),
            ADeviceToWatchOn::rated('Anything with a browser', 'The web player', HowWellADeviceIsServed::Fallback, 'Some formats are converted as they play', ''),
        ),
        'Every one of these works on the home network only',
        'Nothing is installed on anybody\'s device for them',
        WhyPlaybackMayStruggle::said('Archival', 'This machine cannot transcode 4K in hardware', 'Choose the Balanced preset'),
        TheTroubles::of(
            SomethingThatGoesWrong::said('It keeps buffering', APossibleCause::said('The Wi-Fi is weak', 'Only far from the router', 'Move closer')),
            SomethingThatGoesWrong::said('It will not start'),
        ),
    );
}

/** Advice with nothing in its lists and nothing straining. */
function adviceWithNothingListed(): WhatToWatchOn
{
    return WhatToWatchOn::advised(TheDevices::of(), 'Every one of these works on the home network only', 'Nothing is installed for them', WhyPlaybackMayStruggle::nothing(), TheTroubles::of());
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theAdviceScreen(
    AStackThatAdvises $advising,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhichAppToWatchOn {
    $stack = theStackWhoseAdviceIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhichAppToWatchOn($advising, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws every device with the app to use and its rating, fallback among them', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice())))->said();

    expect($drawn)->toContain('An iPhone')
        ->and($drawn)->toContain('Jellyfin for iOS')
        ->and($drawn)->toContain('The web player');

    foreach (HowWellADeviceIsServed::cases() as $rating) {
        expect($drawn)->toContain(__($rating->saidOnTheScreen()));
    }
});

it('says what to use instead of a poorly served device, and what to know before starting', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice())))->said();

    expect($drawn)->toContain(__('stacks.clients.instead', ['instead' => 'A streaming stick']))
        ->and($drawn)->toContain('Surround sound needs a setting changed')
        ->and($drawn)->toContain('Some formats are converted as they play');
});

it('says plainly that it works only at home, and what it will not do for them', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice())))->said();

    expect($drawn)->toContain('Every one of these works on the home network only')
        ->and($drawn)->toContain('Nothing is installed on anybody\'s device for them');
});

it('says why playback may struggle here where something strains it', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice())))->said();

    expect($drawn)->toContain(__('stacks.clients.straining', ['preset' => 'Archival']))
        ->and($drawn)->toContain('This machine cannot transcode 4K in hardware')
        ->and($drawn)->toContain('Choose the Balanced preset');
});

it('says what to do when it does not work, symptom by symptom', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice())))->said();

    expect($drawn)->toContain('It keeps buffering')
        ->and($drawn)->toContain('The Wi-Fi is weak')
        ->and($drawn)->toContain('Only far from the router')
        ->and($drawn)->toContain('Move closer')
        ->and($drawn)->toContain('It will not start')
        ->and($drawn)->toContain(__('stacks.clients.no_causes'));
});

it('advice with nothing listed says so, and says nothing strains playback by saying nothing', function (): void {
    $screen = theAdviceScreen(AStackThatAdvises::advising(adviceWithNothingListed()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.clients.no_devices'))
        ->and($drawn)->toContain(__('stacks.clients.no_trouble'))
        ->and($screen->answer()->strainingPreset)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('health.ask_again')]);
});

it('a stack that could not be asked is not advice with nothing in it', function (): void {
    $screen = theAdviceScreen(AStackThatAdvises::met(Obstacle::StackDidNotAnswer));
    $answer = $screen->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and([
            $answer->devices, $answer->onlyAtHome, $answer->nothingIsInstalled, $answer->strainingPreset,
            $answer->strainingCaution, $answer->strainingInstead, $answer->troubles,
        ])->toBe([[], '', '', '', '', '', []])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.clients.no_devices'));
});

it('a session that has ended is not advice with nothing in it', function (): void {
    $advising = AStackThatAdvises::advising(adviceForEveryDevice());
    $answer = theAdviceScreen($advising, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->devices)->toBe([])
        ->and($advising->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theAdviceScreen(AStackThatAdvises::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseAdviceIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $advising = AStackThatAdvises::advising(adviceForEveryDevice());
    $screen = theAdviceScreen($advising);

    $screen->answer();
    $screen->answer();

    expect($advising->askings())->toBe(1)
        ->and($advising->wasGivenASession())->toBeTrue()
        ->and($advising->askedAbout()?->id()->stored())->toBe(theStackWhoseAdviceIsRead()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $advising = AStackThatAdvises::met(Obstacle::DeviceHasNoNetwork);
    $screen = theAdviceScreen($advising);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($advising->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theAdviceScreen(AStackThatAdvises::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->clients()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theAdviceScreen(AStackThatAdvises::advising(adviceForEveryDevice()))->render()->name())->toBe('operator::which-app-to-watch-on');
});
