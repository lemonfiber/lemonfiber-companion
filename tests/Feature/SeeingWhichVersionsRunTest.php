<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhichVersionsRunHere;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatNamesItsVersions;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Which versions the machine runs, and what the running release changed.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseVersionsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A machine whose notes describe it, running a release the household would notice. */
function versionsWithTheirNotes(bool $withdrawn = false, bool $noticed = true, string $engine = 'Docker Compose version v2.29.1'): WhatRunsHere
{
    return WhatRunsHere::reported(
        '0.16.0',
        '0.9.0',
        $engine,
        HowTheNotesStand::Current,
        Release::called('0.16.0', noticeable: $noticed, withdrawn: $withdrawn, delivers: WhatAReleaseDelivers::said('Plugins')),
        AGroupOfChanges::titled('New', 'Plugins can be installed', 'The panel shows the forwarded port'),
        AGroupOfChanges::titled('Fixed', 'A stuck download is said once'),
    );
}

/** A machine whose notes stand as given, naming a release or none. */
function versionsWhoseNotesAre(HowTheNotesStand $notes, bool $named = true): WhatRunsHere
{
    return $named
        ? WhatRunsHere::reported('0.16.0', '0.9.0', '', $notes, Release::called('0.16.0', noticeable: true, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()), AGroupOfChanges::titled('New', 'Something that must not be drawn'))
        : WhatRunsHere::namingNoRelease('0.16.0', '0.9.0', '', $notes);
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. */
function theVersionsScreen(
    AStackThatNamesItsVersions $reading,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhichVersionsRunHere {
    $stack = theStackWhoseVersionsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhichVersionsRunHere($reading, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('names each version with what it is', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes())))->said();

    expect($drawn)->toContain(__('stacks.versions.title'))
        ->and($drawn)->toContain(__('stacks.versions.lemonfiber', ['version' => '0.16.0']))
        ->and($drawn)->toContain(__('stacks.versions.lemonfiber_means'))
        ->and($drawn)->toContain(__('stacks.versions.stack', ['version' => '0.9.0']))
        ->and($drawn)->toContain(__('stacks.versions.stack_means'))
        ->and($drawn)->toContain(__('stacks.versions.engine', ['version' => 'Docker Compose version v2.29.1']))
        ->and($drawn)->toContain(__('stacks.versions.engine_means'));
});

it('says the container engine is not known where it could not be asked, rather than drawing a blank', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes(engine: ''))))->said();

    expect($drawn)->toContain(__('stacks.versions.engine_unknown'))
        ->and($drawn)->toContain(__('stacks.versions.engine_means'))
        ->and($drawn)->not->toContain(__('stacks.versions.engine', ['version' => '']));
});

it('shows what the running release changed, group by group, and whether the household would notice', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes())))->said();

    expect($drawn)->toContain(__('updates.what_it_changed', ['version' => '0.16.0']))
        ->and($drawn)->toContain(__('updates.would_be_noticed'))
        ->and($drawn)->toContain('New')
        ->and($drawn)->toContain('Plugins can be installed')
        ->and($drawn)->toContain('The panel shows the forwarded port')
        ->and($drawn)->toContain('Fixed')
        ->and($drawn)->toContain('A stuck download is said once')
        ->and($drawn)->not->toContain(__('updates.running_withdrawn'));

    $quiet = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes(noticed: false))))->said();

    expect($quiet)->toContain(__('updates.would_not_be_noticed'))
        ->and($quiet)->not->toContain(__('updates.would_be_noticed'));
});

it('says a running release that was taken back is withdrawn', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes(withdrawn: true))))->said();

    expect($drawn)->toContain(__('updates.running_withdrawn'));
});

it('says notes not written yet, and never draws them as current', function (HowTheNotesStand $notes, bool $named): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWhoseNotesAre($notes, $named))))->said();

    expect($drawn)->toContain(__('stacks.versions.notes_pending'))
        ->and($drawn)->toContain(__('stacks.versions.notes_pending_means'))
        ->and($drawn)->not->toContain(__('updates.what_it_changed', ['version' => '0.16.0']))
        ->and($drawn)->not->toContain('Something that must not be drawn');
})->with([
    'pending, naming the release' => [HowTheNotesStand::Pending, true],
    'pending, naming none' => [HowTheNotesStand::Pending, false],
    'current, naming none' => [HowTheNotesStand::Current, false],
]);

it('says notes out of step, and draws none of them or what they claim', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWhoseNotesAre(HowTheNotesStand::Stale))))->said();

    expect($drawn)->toContain(__('stacks.versions.notes_stale'))
        ->and($drawn)->toContain(__('stacks.versions.notes_stale_means'))
        ->and($drawn)->not->toContain('Something that must not be drawn')
        ->and($drawn)->not->toContain(__('updates.running_withdrawn'))
        ->and($drawn)->not->toContain(__('updates.what_it_changed', ['version' => '0.16.0']));
});

it('offers nothing but asking again', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes())))->offers())
        ->toContain(__('health.ask_again'))
        ->toHaveCount(1);
});

it('a stack that could not be asked names no version', function (): void {
    $answer = theVersionsScreen(AStackThatNamesItsVersions::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->said())
        ->and([$answer->lemonfiber, $answer->stack, $answer->engine, $answer->release, $answer->notesSaid, $answer->noticedSaid])
        ->toBe(['', '', '', '', '', ''])
        ->and($answer->changes)->toBe([]);
});

it('a session that has ended asks nothing', function (): void {
    $reading = AStackThatNamesItsVersions::with(versionsWithTheirNotes());
    $answer = theVersionsScreen($reading, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->lemonfiber)->toBe('')
        ->and($reading->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theVersionsScreen(AStackThatNamesItsVersions::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseVersionsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, and again when asked to', function (): void {
    $reading = AStackThatNamesItsVersions::with(versionsWithTheirNotes());
    $screen = theVersionsScreen($reading);

    $screen->answer();
    $screen->answer();
    expect($reading->askings())->toBe(1);

    $screen->again();
    $screen->answer();
    expect($reading->askings())->toBe(2);
});

it('the way here and the way back are routes', function (): void {
    $screen = theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes()));

    expect(NativeRouter::resolve($screen->goes()->ofItself()->versions()))->not->toBeNull()
        ->and($screen->goes()->ofItself()->versions())->toBe(AStacksScreen::Versions->forTheStack($screen->stack()->id()))
        ->and(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theVersionsScreen(AStackThatNamesItsVersions::with(versionsWithTheirNotes()))->render()->name())->toBe('operator::which-versions-run-here');
});
