<?php

declare(strict_types=1);

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePiecesOfABundle;
use Modules\Kernel\Api\TheTermsOfABundle;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhenABundleWasTaken;
use Modules\Kernel\Api\WhereABundleIs;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\AskingForHelpHere;
use Modules\Operator\Internal\ViewModels\ABundleAsShown;
use Modules\Operator\Internal\ViewModels\APieceAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatBundles;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatABundleSays;
use Tests\Support\WhatTheDeviceWouldDraw;

// Asking for help: choosing what goes in a support bundle, the description,
// the second yes that writes it, and a refusal drawn as one.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The machine help is asked about. */
function theStackHelpIsAskedAbout(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a case says. */
function theHelpScreen(AStackThatBundles $helping, ?AKeychainInMemory $keychain = null, bool $signedIn = true): AskingForHelpHere
{
    $stack = theStackHelpIsAskedAbout();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new AskingForHelpHere($helping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen whose bundle the stack has described. */
function aBundleDescribedOnTheScreen(?AStackThatBundles $helping = null): AskingForHelpHere
{
    $screen = theHelpScreen($helping ?? AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::described())));
    $screen->describe();
    $screen->whileItRuns();

    return $screen;
}

/**
 * Every choice a bundle asked for carried, as one comparable row.
 *
 * @return array{writes: bool, lines: int, filenames: string, revealing: list<string>}
 */
function whatTheBundleAskedFor(ABundleAsked $asked): array
{
    return [
        'writes' => $asked->writes(),
        'lines' => $asked->lines()->figure(),
        'filenames' => $asked->filenames()->value,
        'revealing' => WhatABundleSays::namesOf($asked->revealing()),
    ];
}

/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, string> $with
 */
function aLineOfTheHelp(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

it('opens on a description of the careful bundle, which writes nothing, and asks once', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(whatTheBundleAskedFor(...), $helping->asked()))->toBe([
        ['writes' => false, 'lines' => 200, 'filenames' => 'replaced', 'revealing' => []],
    ])
        ->and($screen->answer()->isWorking)->toBeTrue()
        ->and($drawn)->toContain(__('stacks.help.gathering'))
        ->and($helping->followed())->toBe([]);
});

it('offers the choices, and asks the stack nothing until they are described', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->startOver();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.help.what_goes_in'))
        ->and($drawn->said())->toContain(trans_choice('stacks.help.lines', 200))
        ->and($drawn->said())->toContain(__('stacks.help.filenames_replaced'))
        ->and($drawn->said())->toContain(__('stacks.help.reveals_nothing'))
        ->and($drawn->offers())->toContain(trans_choice('stacks.help.take_lines', 50))
        ->and($drawn->offers())->toContain(trans_choice('stacks.help.take_lines', 1000))
        ->and($drawn->offers())->toContain(__('stacks.help.show_filenames'))
        ->and($drawn->offers())->toContain(__('stacks.help.name_it'))
        ->and($drawn->offers())->toContain(__('stacks.help.describe'))
        ->and($screen->windows())->toBe([50, 200, 1000])
        ->and($screen->answer()->wasAsked)->toBeFalse()
        ->and($helping->asked())->toBe([])
        ->and($helping->followed())->toBe([]);
});

it('takes a log window it offers, and ignores one it does not', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->chooseLines(1000);
    $screen->chooseLines(7);
    $screen->describe();

    expect($screen->lines)->toBe(1000)
        ->and(whatTheBundleAskedFor($helping->asked()[0])['lines'])->toBe(1000);
});

it('shows media filenames only when asked, and replaces them again', function (): void {
    $screen = theHelpScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning()));
    $screen->startOver();
    $screen->showFilenames();
    $shown = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->showsFilenames())->toBeTrue()
        ->and($shown->said())->toContain(__('stacks.help.filenames_shown'))
        ->and($shown->offers())->toContain(__('stacks.help.replace_filenames'));

    $screen->replaceFilenames();

    expect($screen->showsFilenames())->toBeFalse();
});

it('asks about one setting by the name typed, and reveals nothing until its own yes', function (): void {
    $screen = theHelpScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning()));
    $screen->startOver();
    $screen->naming = '  SONARR_URL ';
    $screen->nameASetting();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->revealing)->toBe('SONARR_URL')
        ->and($screen->naming)->toBe('')
        ->and($screen->revealedNames())->toBe([])
        ->and($drawn->said())->toContain(__('stacks.help.reveal_this', ['name' => 'SONARR_URL']))
        ->and($drawn->offers())->toContain(__('stacks.help.reveal', ['name' => 'SONARR_URL']))
        ->and($drawn->offers())->toContain(__('stacks.help.keep_it_hidden'));
});

it('reveals only the one setting named, one yes at a time', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->startOver();

    foreach (['SONARR_URL', 'RADARR_URL'] as $named) {
        $screen->naming = $named;
        $screen->nameASetting();
        $screen->reveal();
    }

    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->describe();
    $screen->whileItRuns();
    $screen->answer();

    expect($screen->revealing)->toBeNull()
        ->and($helping->followed())->toHaveCount(1)
        ->and($screen->revealedNames())->toBe(['SONARR_URL', 'RADARR_URL'])
        ->and($drawn->said())->toContain(__('stacks.help.reveals', ['name' => 'SONARR_URL']))
        ->and($drawn->offers())->toContain(__('stacks.help.take_back', ['name' => 'RADARR_URL']))
        ->and(whatTheBundleAskedFor($helping->asked()[0])['revealing'])->toBe(['SONARR_URL', 'RADARR_URL']);
});

it('reveals nothing for a blank name, a yes with nothing named, or a setting left hidden', function (): void {
    $screen = theHelpScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning()));
    $screen->naming = '   ';
    $screen->nameASetting();
    $screen->reveal();

    expect($screen->revealing)->toBeNull()
        ->and($screen->naming)->toBe('   ');

    $screen->naming = 'SONARR_URL';
    $screen->nameASetting();
    $screen->keepItHidden();
    $screen->reveal();

    expect($screen->revealing)->toBeNull()
        ->and($screen->revealedNames())->toBe([]);
});

it('takes back a setting agreed to, and nothing for a blank name', function (): void {
    $screen = theHelpScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning()));

    foreach (['SONARR_URL', 'RADARR_URL'] as $named) {
        $screen->naming = $named;
        $screen->nameASetting();
        $screen->reveal();
    }

    $screen->takeBack(' ');
    $screen->takeBack('SONARR_URL');

    expect($screen->revealedNames())->toBe(['RADARR_URL']);
});

it('describes the bundle chosen without writing it, and says it is being gathered', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->showFilenames();
    $screen->describe();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(whatTheBundleAskedFor(...), $helping->asked()))->toBe([
        ['writes' => false, 'lines' => 200, 'filenames' => 'shown', 'revealing' => []],
    ])
        ->and($screen->answer()->isWorking)->toBeTrue()
        ->and($screen->handle)->toBe(AStackThatBundles::THE_JOB)
        ->and($drawn)->toContain(__('stacks.help.gathering'))
        ->and($drawn)->toContain(__('health.every.while_work_runs', ['count' => 5]))
        ->and($helping->followed())->toBe([]);
});

it('asks after a bundle on its cadence while it is gathered, by the handle it was answered with', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->describe();
    $screen->whileItRuns();
    $screen->answer();

    expect($helping->followed())->toHaveCount(1)
        ->and($helping->followed()[0]->shown())->toBe(AStackThatBundles::THE_JOB);
});

it('stops asking once the bundle has been described', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::described()));
    $screen = aBundleDescribedOnTheScreen($helping);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($helping->followed())->toHaveCount(1);
});

it('draws the description whole, what it reveals and what is missing among it, and writes nothing', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::described()));
    $screen = aBundleDescribedOnTheScreen($helping);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->bundle)->toEqual(new ABundleAsShown(
        bytes: 48_213,
        whereSaid: 'stacks.help.would_go',
        where: WhatABundleSays::WOULD_GO,
        window: 'the last 200 lines of each service',
        filenamesSaid: 'stacks.help.filenames_replaced',
        revealed: ['SONARR_URL'],
        pieces: [
            new APieceAsShown(name: 'diagnosis.txt', body: "storage.one-filesystem: passed\n\nvpn.killswitch: failed"),
            new APieceAsShown(name: 'services.txt', body: 'sonarr running'),
        ],
        missing: ['the container engine could not be reached'],
        takenAt: '2026-09-26T10:00:00Z',
        lemonfiber: '1.4.0',
        stack: '2026.09',
    ))
        ->and($screen->answer()->isWritten)->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.help.described'))
        ->and($drawn->said())->toContain(__('stacks.help.would_go', ['path' => WhatABundleSays::WOULD_GO]))
        ->and($drawn->said())->toContain(trans_choice('stacks.help.bytes', 48_213))
        ->and($drawn->said())->toContain(__('stacks.help.taken', ['at' => '2026-09-26T10:00:00Z', 'lemonfiber' => '1.4.0', 'stack' => '2026.09']))
        ->and($drawn->said())->toContain(__('stacks.help.reveals', ['name' => 'SONARR_URL']))
        ->and($drawn->said())->toContain('the last 200 lines of each service')
        ->and($drawn->said())->toContain(__('stacks.help.filenames_replaced'))
        ->and($drawn->said())->toContain(__('stacks.help.missing', ['what' => 'the container engine could not be reached']))
        ->and($drawn->said())->toContain('diagnosis.txt')
        ->and($drawn->said())->toContain('sonarr running')
        ->and($drawn->offers())->toContain(__('stacks.help.write'))
        ->and(array_map(whatTheBundleAskedFor(...), $helping->asked()))->toHaveCount(1);
});

it('writes the bundle described, on its own yes, with every choice it was described with', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::described()));
    $screen = theHelpScreen($helping);
    $screen->chooseLines(50);
    $screen->naming = 'SONARR_URL';
    $screen->nameASetting();
    $screen->reveal();
    $screen->describe();
    $screen->whileItRuns();
    $screen->chooseLines(1000);
    $screen->write();

    expect(array_map(whatTheBundleAskedFor(...), $helping->asked()))->toBe([
        ['writes' => false, 'lines' => 50, 'filenames' => 'replaced', 'revealing' => ['SONARR_URL']],
        ['writes' => true, 'lines' => 50, 'filenames' => 'replaced', 'revealing' => ['SONARR_URL']],
    ])
        ->and($screen->answer()->isWorking)->toBeTrue();
});

it('draws a written bundle as written, where it is, and offers no second write', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::written()));
    $screen = aBundleDescribedOnTheScreen($helping);
    $screen->write();
    $screen->whileItRuns();
    $screen->write();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->isWritten)->toBeTrue()
        ->and($screen->answer()->bundle?->whereSaid)->toBe('stacks.help.written_at')
        ->and($drawn->said())->toContain(__('stacks.help.written'))
        ->and($drawn->said())->toContain(__('stacks.help.written_at', ['path' => WhatABundleSays::WOULD_GO]))
        ->and($drawn->offers())->not->toContain(__('stacks.help.write'))
        ->and(array_map(static fn(ABundleAsked $asked): bool => $asked->writes(), $helping->asked()))->toBe([false, true]);
});

it('writes nothing until the stack has answered with a description', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping);
    $screen->write();
    $screen->describe();
    $screen->write();

    expect(array_map(static fn(ABundleAsked $asked): bool => $asked->writes(), $helping->asked()))->toBe([false]);
});

it('never sends a write again that met an obstacle: asking again describes', function (): void {
    $helping = AStackThatBundles::whichGatheredOnceThenMet(HowTheBundleIsGoing::done(WhatABundleSays::described()), Obstacle::StackDidNotAnswer);
    $screen = aBundleDescribedOnTheScreen($helping);
    $screen->write();

    expect($screen->handle)->toBeNull()
        ->and($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said());

    $screen->again();
    $screen->answer();

    expect(array_map(static fn(ABundleAsked $asked): bool => $asked->writes(), $helping->asked()))->toBe([false, true, false])
        ->and($helping->followed())->toHaveCount(1);
});

it('draws a bundle the stack refused as its refusal, and offers the choices rather than asking again', function (): void {
    $screen = aBundleDescribedOnTheScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::refused(WhatABundleSays::A_LEAK)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->refused)->toBe(WhatABundleSays::A_LEAK)
        ->and($screen->answer()->bundle)->toBeNull()
        ->and($drawn->said())->toContain(__('stacks.help.refused'))
        ->and($drawn->said())->toContain(WhatABundleSays::A_LEAK)
        ->and($drawn->said())->toContain(__('stacks.help.refused_wrote_nothing'))
        ->and($drawn->offers())->toContain(__('stacks.help.start_over'))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($drawn->offers())->not->toContain(__('stacks.help.write'));
});

it('goes back to the choices, keeping them, and forgets the bundle it was following', function (): void {
    $screen = aBundleDescribedOnTheScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::refused(WhatABundleSays::A_LEAK)));
    $screen->chooseLines(50);
    $screen->startOver();

    expect($screen->asked)->toBeNull()
        ->and($screen->handle)->toBeNull()
        ->and($screen->answer()->wasAsked)->toBeFalse()
        ->and($screen->lines)->toBe(50)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.help.what_goes_in'));
});

it('says the stack no longer knows what became of a bundle, which is not a failure', function (): void {
    $screen = aBundleDescribedOnTheScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::ended()));

    expect($screen->answer()->hasEnded)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.help.no_outcome'));
});

it('says a bundle revealing nothing, missing nothing and placed nowhere, in words', function (): void {
    $bundle = ABundle::reported(
        512,
        WhereABundleIs::unsaid(),
        TheTermsOfABundle::stated('the last 50 lines of each service', WhatFilenamesShow::Shown, SettingsToReveal::none()),
        ThePiecesOfABundle::of(),
        Remarks::of(),
        WhenABundleWasTaken::at('2026-09-26T10:00:00Z', '1.4.0', '2026.09'),
    );
    $screen = aBundleDescribedOnTheScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::done($bundle)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->bundle?->where)->toBe('')
        ->and($drawn)->toContain(aLineOfTheHelp('stacks.help.would_go_unsaid'))
        ->and($drawn)->toContain(__('stacks.help.reveals_nothing'))
        ->and($drawn)->toContain(__('stacks.help.nothing_missing'))
        ->and($drawn)->toContain(__('stacks.help.filenames_shown'));
});

it('draws what stood in the way of describing, and asking again describes again', function (): void {
    $helping = AStackThatBundles::met(Obstacle::StackDidNotAnswer);
    $screen = theHelpScreen($helping);
    $screen->describe();

    expect($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->handle)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));

    $screen->again();
    $screen->answer();

    expect(array_map(static fn(ABundleAsked $asked): bool => $asked->writes(), $helping->asked()))->toBe([false, false]);
});

it('lets go of a session the stack refused, whether asking or asking after', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theHelpScreen(AStackThatBundles::met(Obstacle::CredentialWasRefused), $keychain);
    $screen->describe();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackHelpIsAskedAbout()->id()))->toBeFalse();

    $keychain = AKeychainInMemory::working();
    $refusing = AStackThatBundles::whichGathered(HowTheBundleIsGoing::met(Obstacle::CredentialWasRefused));
    $screen = theHelpScreen($refusing, $keychain);
    $screen->describe();
    $screen->whileItRuns();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackHelpIsAskedAbout()->id()))->toBeFalse();
});

it('keeps a session through an obstacle that says nothing about it', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theHelpScreen(AStackThatBundles::met(Obstacle::StackDidNotAnswer), $keychain);
    $screen->describe();

    expect($keychain->isHolding(theStackHelpIsAskedAbout()->id()))->toBeTrue();
});

it('asks nothing of a stack this device holds no session for', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping, signedIn: false);
    $screen->describe();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($helping->asked())->toBe([]);
});

it('follows nothing for a stack this device stopped holding a session for', function (): void {
    $keychain = AKeychainInMemory::working();
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    $screen = theHelpScreen($helping, $keychain);
    $screen->describe();
    $keychain->forget(theStackHelpIsAskedAbout()->id());
    $screen->again();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($helping->followed())->toBe([]);
});

it('is reached from the machine it is about, and goes back to it', function (): void {
    $screen = theHelpScreen(AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning()));

    expect(NativeRouter::resolve($screen->goes()->ofItself()->help()))->not->toBeNull()
        ->and($screen->goes()->ofItself()->help())->toBe(sprintf('/stacks/%s/help', theStackHelpIsAskedAbout()->id()->stored()))
        ->and($screen->render()->name())->toBe('operator::asking-for-help-here')
        ->and($screen->cadence()->seconds())->toBe(5);
});
