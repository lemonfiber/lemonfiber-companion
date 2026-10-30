<?php

declare(strict_types=1);

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatSwitches;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

/**
 * @return WhatBecameOfTheWork<TheSwitch>
 */
function aSwitchReported(WhetherItWasRehearsed $did): WhatBecameOfTheWork
{
    return WhatBecameOfTheWork::answered(
        TheSwitch::said($did, TheServicesNamed::of('qBittorrent'), TheServicesNamed::of('Jellyseerr'), TheServicesNamed::of('Jellyfin'))
            ->stoppingWith(TheCommandLine::of('docker', 'compose', 'stop', 'qbittorrent')),
    );
}

/**
 * @param WhatBecameOfTheWork<TheSwitch>|null $rehearsal
 * @param WhatBecameOfTheWork<TheSwitch>|null $switch
 */
function aStackThatSwitchesItsForms(?WhatBecameOfTheWork $rehearsal = null, ?WhatBecameOfTheWork $switch = null): AStackThatSwitches
{
    return AStackThatSwitches::answering($rehearsal ?? aSwitchReported(WhetherItWasRehearsed::Rehearsed), $switch ?? aSwitchReported(WhetherItWasRehearsed::CarriedOut));
}

function theScreenThatSwitches(AStackThatSwitches $switching, ?AStackThatSupervises $supervising = null, ?AKeychainInMemory $keychain = null): WhatThisStackRuns
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatThisStackRuns(
        $supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        WhatThePhoneKeeps::noListingYet(),
        $switching,
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    return $screen;
}

/** @return list<string> */
function theFormsSentToSwitch(TheFormsToSwitchTo $forms): array
{
    return array_map(static fn(Form $form): string => $form->named(), iterator_to_array($forms->forms(), preserve_keys: false));
}

function aRehearsedSwitchToTheLibrary(AStackThatSwitches $switching): WhatThisStackRuns
{
    $screen = theScreenThatSwitches($switching);
    $screen->toggleForm('library');
    $screen->rehearseTheSwitch();
    $screen->whileItSwitches();
    $screen->howTheSwitchIsGoing();

    return $screen;
}

it('offers each form the stack declares to choose for the active set, and asks nothing yet', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theScreenThatSwitches($switching));

    expect($drawn->said())->toContain(__('stacks.switch.title'), __('stacks.switch.what_it_does'))
        ->and($drawn->offers())->toContain('library', 'full', __('stacks.switch.rehearse'))
        ->and($drawn->offers())->not->toContain(__('stacks.switch.apply'))
        ->and($switching->rehearsed())->toBe([]);
});

it('says a switch to nothing on the screen rather than sending it, and chooses and unchooses a form', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $screen = theScreenThatSwitches($switching);
    $screen->toggleForm('full');
    $screen->toggleForm('full');
    $screen->rehearseTheSwitch();

    expect($screen->howTheSwitchIsGoing()->notAskable)->toBe('stacks.switch.none_chosen')
        ->and($switching->rehearsed())->toBe([]);
});

it('sends only forms the stack declares, whatever else is chosen', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $screen = theScreenThatSwitches($switching);
    $screen->toggleForm('a-form-nobody-declared');
    $screen->toggleForm('full');
    $screen->toggleForm('library');
    $screen->rehearseTheSwitch();

    expect(array_map(theFormsSentToSwitch(...), $switching->rehearsed()))->toBe([['library', 'full']]);
});

it('rehearses, follows the work, and draws what would stop, start and keep labelled as a rehearsal', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $screen = theScreenThatSwitches($switching);
    $screen->toggleForm('library');
    $screen->rehearseTheSwitch();

    expect($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileItSwitches();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $switching->followed()))->toBe([AStackThatSwitches::THE_REHEARSAL])
        ->and($drawn->said())->toContain(__('stacks.switch.did.rehearsed'), __('stacks.switch.stops'), 'qBittorrent', __('stacks.switch.starts'), 'Jellyseerr', __('stacks.switch.keeps'), 'Jellyfin', __('stacks.switch.not_restarted'), 'docker compose stop qbittorrent')
        ->and($drawn->offers())->toContain(__('stacks.switch.apply'))
        ->and($switching->carriedOut())->toBe([]);
});

it('makes the switch the rehearsal was asked with, not what is chosen when the yes is tapped', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $screen = aRehearsedSwitchToTheLibrary($switching);
    $screen->toggleForm('full');
    $screen->makeTheSwitch();
    $screen->whileItSwitches();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(theFormsSentToSwitch(...), $switching->carriedOut()))->toBe([['library']])
        ->and($drawn->said())->toContain(__('stacks.switch.did.carried_out'))
        ->and($drawn->offers())->not->toContain(__('stacks.switch.apply'));
});

it('sends no yes where nothing was rehearsed, or where the last answer was not a rehearsal', function (): void {
    $switching = aStackThatSwitchesItsForms();
    theScreenThatSwitches($switching)->makeTheSwitch();

    $made = aStackThatSwitchesItsForms(rehearsal: aSwitchReported(WhetherItWasRehearsed::CarriedOut));
    $screen = aRehearsedSwitchToTheLibrary($made);
    $screen->makeTheSwitch();

    expect($switching->carriedOut())->toBe([])
        ->and($made->carriedOut())->toBe([])
        ->and($screen->howTheSwitchIsGoing()->change?->mayBeApplied)->toBeFalse();
});

it('says the session ended where it went while the switch was rehearsed', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theScreenThatSwitches(aStackThatSwitchesItsForms(), keychain: $keychain);
    $screen->toggleForm('library');
    $screen->rehearseTheSwitch();
    $keychain->forget(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id());
    $screen->whileItSwitches();

    expect($screen->howTheSwitchIsGoing()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->awaitsAnOutcome())->toBeFalse();
});

it('draws a refusal in the stack\'s own words, an outcome the stack no longer has, and what stood in the way', function (): void {
    $why = ARefusalInItsWords::said('this stack declares no form called archive', '', WhatTheRefusalNamed::nothing(), 'Choose one of: library, full');
    $refused = aRehearsedSwitchToTheLibrary(aStackThatSwitchesItsForms(rehearsal: WhatBecameOfTheWork::refused($why)));
    $ended = aRehearsedSwitchToTheLibrary(aStackThatSwitchesItsForms(rehearsal: WhatBecameOfTheWork::ended()));
    $met = aRehearsedSwitchToTheLibrary(AStackThatSwitches::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('stacks.switch.refused'), 'this stack declares no form called archive')
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('stacks.switch.no_outcome'))
        ->and(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__('stacks.switch.unread'), __(KindOfObstacle::StackDidNotAnswer->said()));
});

it('reads what the stack runs again once a switch is made, and not after a rehearsal', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theScreenThatSwitches(aStackThatSwitchesItsForms(), $supervising);
    $screen->toggleForm('library');
    $screen->rehearseTheSwitch();
    $screen->whileItSwitches();
    $screen->howTheSwitchIsGoing();
    WhatTheDeviceWouldDraw::by($screen);
    $readAfterTheRehearsal = $supervising->askings();
    $screen->makeTheSwitch();
    $screen->whileItSwitches();
    $screen->howTheSwitchIsGoing();
    WhatTheDeviceWouldDraw::by($screen);

    expect($supervising->askings())->toBeGreaterThan($readAfterTheRehearsal);
});

it('makes a rehearsed switch once however often the yes is tapped, and never one something else replaced', function (): void {
    $switching = aStackThatSwitchesItsForms();
    $screen = aRehearsedSwitchToTheLibrary($switching);
    $screen->makeTheSwitch();
    $screen->makeTheSwitch();

    $replaced = aStackThatSwitchesItsForms();
    $other = aRehearsedSwitchToTheLibrary($replaced);
    $other->toggleForm('library');
    $other->rehearseTheSwitch();
    $other->makeTheSwitch();

    expect($switching->carriedOut())->toHaveCount(1)
        ->and($replaced->carriedOut())->toBe([])
        ->and($other->howTheSwitchIsGoing()->notAskable)->toBe('stacks.switch.none_chosen');
});
