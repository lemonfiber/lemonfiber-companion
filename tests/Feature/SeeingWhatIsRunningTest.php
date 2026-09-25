<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsRunningHere;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatChecksItself;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Which version of lemonfiber the machine runs, and whether a newer one exists.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseCopyIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What an update brings and leaves behind, as every copy here says it. */
function whatUpdatingBrings(): WhatAnUpdateWouldBring
{
    return WhatAnUpdateWouldBring::said('The program, and the stack definition it ships with', 'Your settings and library are left alone; the stack restarts');
}

/** A copy lemonfiber's installer put there, with a newer version out and a command to take it. */
function aCopyWithANewerVersion(): ThisCopyOfLemonfiber
{
    return ThisCopyOfLemonfiber::reported('0.15.0', HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Installer, ''), WhereThisCopyStands::UpdateAvailable, WhatIsReleased::said('0.16.0', 'Plugins can be installed from the phone'), '', HowItWouldBeUpdated::byRunning('lemonfiber update self'), whatUpdatingBrings());
}

/** A copy whose installation nobody could tell, whose check failed, with a reason and no command. */
function aCopyNobodyCouldPlace(): ThisCopyOfLemonfiber
{
    return ThisCopyOfLemonfiber::reported('0.15.0', HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Untellable, ''), WhereThisCopyStands::CheckFailed, WhatIsReleased::nothing(), 'The release page could not be reached', HowItWouldBeUpdated::insteadBecause('How it was installed could not be told, so there is nothing exact to type'), whatUpdatingBrings());
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theCopyScreen(
    AStackThatChecksItself $checking,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatIsRunningHere {
    $stack = theStackWhoseCopyIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatIsRunningHere($checking, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('says which version runs, how it was installed, and where it stands', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreen(AStackThatChecksItself::with(aCopyWithANewerVersion())))->said();

    expect($drawn)->toContain(__('stacks.itself.running', ['version' => '0.15.0']))
        ->and($drawn)->toContain(__(HowLemonfiberWasInstalled::Installer->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhereThisCopyStands::UpdateAvailable->saidOnTheScreen()))
        ->and($drawn)->toContain(__('stacks.itself.offered', ['version' => '0.16.0']))
        ->and($drawn)->toContain('Plugins can be installed from the phone');
});

it('an installation nobody could tell and a check that failed are said, never read as replaceable or current', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreen(AStackThatChecksItself::with(aCopyNobodyCouldPlace())))->said();

    expect($drawn)->toContain(__(HowLemonfiberWasInstalled::Untellable->saidOnTheScreen()))
        ->and($drawn)->toContain(__(WhereThisCopyStands::CheckFailed->saidOnTheScreen()))
        ->and($drawn)->toContain('The release page could not be reached')
        ->and($drawn)->not->toContain(__(WhereThisCopyStands::Current->saidOnTheScreen()))
        ->and($drawn)->not->toContain(__('stacks.itself.run_at_the_machine'));
});

it('shows the command to run at the machine, and offers nothing that would run it', function (): void {
    $screen = theCopyScreen(AStackThatChecksItself::with(aCopyWithANewerVersion()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.itself.run_at_the_machine'))
        ->and($drawn)->toContain('lemonfiber update self')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('health.ask_again')]);
});

it('shows why there is no command where there is none, instead of a command', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreen(AStackThatChecksItself::with(aCopyNobodyCouldPlace())))->said();

    expect($drawn)->toContain('How it was installed could not be told, so there is nothing exact to type');
});

it('says who owns a copy another tool keeps up to date', function (): void {
    $brew = ThisCopyOfLemonfiber::reported('0.15.0', HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Homebrew, 'brew'), WhereThisCopyStands::ManagedExternally, WhatIsReleased::said('0.16.0', ''), '', HowItWouldBeUpdated::byRunning('brew upgrade lemonfiber'), whatUpdatingBrings());
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreen(AStackThatChecksItself::with($brew)))->said();

    expect($drawn)->toContain(__('stacks.itself.owner', ['owner' => 'brew']))
        ->and($drawn)->toContain(__(WhereThisCopyStands::ManagedExternally->saidOnTheScreen()))
        ->and($drawn)->toContain('brew upgrade lemonfiber');
});

it('says what an update brings and leaves behind, and that the services are updated elsewhere', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreen(AStackThatChecksItself::with(aCopyWithANewerVersion())))->said();

    expect($drawn)->toContain('The program, and the stack definition it ships with')
        ->and($drawn)->toContain('Your settings and library are left alone; the stack restarts')
        ->and($drawn)->toContain(__('stacks.itself.not_the_services'));
});

it('a stack that could not be asked is not a copy that is up to date', function (): void {
    $answer = theCopyScreen(AStackThatChecksItself::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and([
            $answer->running, $answer->installedSaid, $answer->owner, $answer->standsSaid, $answer->offered,
            $answer->changed, $answer->untold, $answer->command, $answer->instead, $answer->carries, $answer->afterwards,
        ])->toBe(['', '', '', '', '', '', '', '', '', '', '']);
});

it('a session that has ended is not a copy that is up to date', function (): void {
    $checking = AStackThatChecksItself::with(aCopyWithANewerVersion());
    $answer = theCopyScreen($checking, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->running)->toBe('')
        ->and($checking->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theCopyScreen(AStackThatChecksItself::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseCopyIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $checking = AStackThatChecksItself::with(aCopyWithANewerVersion());
    $screen = theCopyScreen($checking);

    $screen->answer();
    $screen->answer();

    expect($checking->askings())->toBe(1)
        ->and($checking->wasGivenASession())->toBeTrue()
        ->and($checking->askedAbout()?->id()->stored())->toBe(theStackWhoseCopyIsRead()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $checking = AStackThatChecksItself::met(Obstacle::DeviceHasNoNetwork);
    $screen = theCopyScreen($checking);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($checking->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theCopyScreen(AStackThatChecksItself::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theCopyScreen(AStackThatChecksItself::with(aCopyWithANewerVersion()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->itself()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theCopyScreen(AStackThatChecksItself::with(aCopyWithANewerVersion()))->render()->name())->toBe('operator::what-is-running-here');
});
