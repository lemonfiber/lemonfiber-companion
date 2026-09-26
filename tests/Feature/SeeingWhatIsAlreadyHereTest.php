<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatMayBeDone;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsAlreadyOnThisMachine;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackWithSomethingAlreadyOnIt;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What is already on a machine, before anything is moved in.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackThatWasSurveyed(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The modes the stack offers, least destructive first, adopting chosen. */
function theModesOnOffer(): TheModes
{
    return TheModes::of(
        AMode::offered('adopt', 'Manages what is here', disturbs: false, preselected: true),
        AMode::offered('side-by-side', 'Runs on other ports beside it', disturbs: false, preselected: false),
        AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: false),
    );
}

/** A survey with something of everything in it. */
function aSurveyOfAMachineInUse(): TheSurvey
{
    return TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(
            AProjectStanding::named(
                'media',
                AServiceStanding::found('sonarr', ThePortsItPublishes::of(8989, 9898), running: true, adoptable: true),
                AServiceStanding::found('tautulli', ThePortsItPublishes::of(), running: false, adoptable: false),
            ),
        ),
        conflicts: ThePortsHeld::of(APortHeld::of(8989, 'sonarr', 'media')),
        unsupported: WhatIsUnsupported::these(Unsupported::of('media/tautulli', 'lemonfiber does not run it')),
        beside: ThePortsMoved::of(APortMoved::of('sonarr', 8989, 8990)),
        linking: WhatLinkingCosts::cannotLink('Downloads and the library are on two filesystems', 'Every import is a second copy', 'Keep both under one mount', 'ext4', 'nfs'),
        choices: WhatMayBeDone::offered(theModesOnOffer(), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );
}

/** A survey that found no project, having looked or not. */
function aSurveyThatFoundNoProject(bool $looked): TheSurvey
{
    return TheSurvey::reported(
        looked: $looked,
        standing: WhatStandsHere::of(),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        choices: WhatMayBeDone::offered(theModesOnOffer(), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theSurveyScreen(
    AStackWithSomethingAlreadyOnIt $movingIn,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatIsAlreadyOnThisMachine {
    $stack = theStackThatWasSurveyed();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatIsAlreadyOnThisMachine($movingIn, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('shows every project and service, whether each runs, and whether it could be taken over', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $services = $screen->answer()->projects[0]->services;

    expect($screen->answer()->looked)->toBeTrue()
        ->and($screen->answer()->projects[0]->project)->toBe('media')
        ->and([$services[0]->service, $services[0]->ports, $services[0]->runningSaid, $services[0]->adoptableSaid])
        ->toBe(['sonarr', '8989, 9898', 'stacks.already_here.running', 'stacks.already_here.adoptable'])
        ->and([$services[1]->service, $services[1]->ports, $services[1]->runningSaid, $services[1]->adoptableSaid])
        ->toBe(['tautulli', '', 'stacks.already_here.stopped', 'stacks.already_here.not_adoptable'])
        ->and($drawn)->toContain(__('stacks.already_here.project', ['project' => 'media']))
        ->and($drawn)->toContain(__('stacks.already_here.ports', ['ports' => '8989, 9898']))
        ->and($drawn)->toContain(__('stacks.already_here.no_ports'))
        ->and($drawn)->toContain(__('stacks.already_here.stopped'))
        ->and($drawn)->toContain(__('stacks.already_here.not_adoptable'));
});

it('draws what it found before any mode', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse())))->said();

    $found = array_search('tautulli', $drawn, strict: true);
    $modes = array_search(__('stacks.already_here.modes'), $drawn, strict: true);

    expect(is_int($found) && is_int($modes) && $found < $modes)->toBeTrue();
});

it('tells a survey that could not look from one that found nothing', function (): void {
    $unread = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyThatFoundNoProject(looked: false)));
    $empty = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyThatFoundNoProject(looked: true)));

    expect($unread->answer()->looked)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($unread)->said())->toContain(__('stacks.already_here.could_not_look'))
        ->and(WhatTheDeviceWouldDraw::by($unread)->said())->not->toContain(__('stacks.already_here.nothing_found'))
        ->and(WhatTheDeviceWouldDraw::by($empty)->said())->toContain(__('stacks.already_here.nothing_found'))
        ->and(WhatTheDeviceWouldDraw::by($empty)->said())->not->toContain(__('stacks.already_here.could_not_look'));
});

it('offers the modes in the stack\'s order, each with what it comes to and whether it disturbs, and chooses only adopting', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $modes = [];

    foreach ($screen->answer()->modes as $mode) {
        $modes[] = [$mode->mode, $mode->what, $mode->disturbsSaid, $mode->preselected];
    }

    expect($modes)->toBe([
        ['adopt', 'Manages what is here', 'stacks.already_here.disturbs_nothing', true],
        ['side-by-side', 'Runs on other ports beside it', 'stacks.already_here.disturbs_nothing', false],
        ['replace', 'Stops the old one', 'stacks.already_here.disturbs', false],
    ])
        ->and($drawn)->toContain(__('stacks.already_here.disturbs'))
        ->and(array_keys($drawn, __('stacks.already_here.preselected'), strict: true))->toHaveCount(1);
});

it('never draws replacement as chosen, even where the stack marked it', function (): void {
    $survey = TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        choices: WhatMayBeDone::offered(TheModes::of(AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: true)), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with($survey));

    expect($screen->answer()->modes[0]->preselected)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.already_here.preselected'));
});

it('names what holds a port in the way, and where a moved service would be reached', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->conflicts[0]->with)->toBe(['port' => '8989', 'wanted_by' => 'sonarr', 'held_by' => 'media'])
        ->and($screen->answer()->beside[0]->with)->toBe(['service' => 'sonarr', 'from' => '8989', 'to' => '8990'])
        ->and($drawn)->toContain(__('stacks.already_here.conflict', ['port' => '8989', 'wanted_by' => 'sonarr', 'held_by' => 'media']))
        ->and($drawn)->toContain(__('stacks.already_here.moved', ['service' => 'sonarr', 'from' => '8989', 'to' => '8990']));
});

it('names what cannot be taken over, with why', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));

    expect($screen->answer()->unsupported[0]->with)->toBe(['what' => 'media/tautulli', 'because' => 'lemonfiber does not run it'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('stacks.already_here.unsupported', ['what' => 'media/tautulli', 'because' => 'lemonfiber does not run it']));
});

it('shows what a layout that cannot link costs and how to fix it, as words with nothing to press', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));
    $linking = $screen->answer()->linking;
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$linking->because, $linking->cost, $linking->remedy, $linking->filesystems])
        ->toBe(['Downloads and the library are on two filesystems', 'Every import is a second copy', 'Keep both under one mount', 'ext4, nfs'])
        ->and($drawn->said())->toContain('Keep both under one mount')
        ->and($drawn->said())->toContain(__('stacks.already_here.remedy_is_yours'))
        ->and($drawn->offers())->toBe([__('health.ask_again')]);
});

it('says where nothing is in the way, and draws nothing about linking where the layout links', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyThatFoundNoProject(looked: true)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    $linking = $screen->answer()->linking;

    expect([$linking->because, $linking->cost, $linking->remedy, $linking->filesystems, $screen->answer()->conflicts, $screen->answer()->beside, $screen->answer()->unsupported])
        ->toBe(['', '', '', '', [], [], []])
        ->and($drawn)->toContain(__('stacks.already_here.no_conflicts'))
        ->and($drawn)->toContain(__('stacks.already_here.none_moved'))
        ->and($drawn)->toContain(__('stacks.already_here.nothing_unsupported'))
        ->and($drawn)->not->toContain(__('stacks.already_here.cannot_link'));
});

it('says nothing about what is in the way where the survey could not look', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyThatFoundNoProject(looked: false))))->said();

    expect($drawn)->not->toContain(__('stacks.already_here.no_conflicts'))
        ->and($drawn)->not->toContain(__('stacks.already_here.nothing_unsupported'))
        ->and($drawn)->toContain(__('stacks.already_here.modes'));
});

it('says so where a project has no services or the stack offers no mode', function (): void {
    $survey = TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(AProjectStanding::named('empty')),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        choices: WhatMayBeDone::offered(TheModes::of(), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );
    $drawn = WhatTheDeviceWouldDraw::by(theSurveyScreen(AStackWithSomethingAlreadyOnIt::with($survey)))->said();

    expect($drawn)->toContain(__('stacks.already_here.no_services'))
        ->and($drawn)->toContain(__('stacks.already_here.no_modes'));
});

it('a stack that could not be asked is not a machine with nothing on it', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::met(Obstacle::StackDidNotAnswer));
    $answer = $screen->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and([$answer->looked, $answer->projects, $answer->conflicts, $answer->beside, $answer->unsupported, $answer->modes])
        ->toBe([false, [], [], [], [], []])
        ->and([$answer->linking->because, $answer->linking->cost, $answer->linking->remedy, $answer->linking->filesystems])
        ->toBe(['', '', '', ''])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.already_here.nothing_found'));
});

it('a session that has ended is not a machine with nothing on it', function (): void {
    $movingIn = AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse());
    $answer = theSurveyScreen($movingIn, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->projects)->toBe([])
        ->and($movingIn->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackThatWasSurveyed()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $movingIn = AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse());
    $screen = theSurveyScreen($movingIn);

    $screen->answer();
    $screen->answer();

    expect($movingIn->askings())->toBe(1)
        ->and($movingIn->wasGivenASession())->toBeTrue()
        ->and($movingIn->askedAbout()?->id()->stored())->toBe(theStackThatWasSurveyed()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $movingIn = AStackWithSomethingAlreadyOnIt::met(Obstacle::DeviceHasNoNetwork);
    $screen = theSurveyScreen($movingIn);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($movingIn->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->alreadyHere()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theSurveyScreen(AStackWithSomethingAlreadyOnIt::with(aSurveyOfAMachineInUse()))->render()->name())->toBe('operator::what-is-already-on-this-machine');
});
