<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;
use Modules\Operator\Internal\Screens\HowTheServicesAreWired;
use Modules\Operator\Internal\ViewModels\AClaimantAsShown;
use Modules\Operator\Internal\ViewModels\AConnectionAsShown;
use Modules\Operator\Internal\ViewModels\TheWiringAsShown;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;
use Modules\Wayfinding\Internal\TheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhatAnswersWhat;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatWires;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

// Wiring the services to each other, and how each connection turned out.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose services are wired. */
function theStackWhoseServicesAreWired(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a stack it knows, running two services, and a keychain holding whatever a test says. Named for this file (`G10`). */
function theWiringScreen(
    AStackThatWires $wiring,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?AStackThatSupervises $supervising = null,
    ?AStackThatSaysWhatAnswersWhat $linking = null,
): HowTheServicesAreWired {
    $stack = theStackWhoseServicesAreWired();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowTheServicesAreWired($wiring, $linking ?? AStackThatSaysWhatAnswersWhat::with(TheLinks::of(WhatNothingFills::none())), $supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A connection in one state, breaking nothing. */
function aConnectionEnded(string $connection, HowAConnectionEnded $ended): AConnection
{
    return AConnection::of($connection, WhatItWouldBreak::nothing(), $ended);
}

/** A run with one connection in every state, and one warning. */
function aRunOfEveryConnection(): TheWiring
{
    return TheWiring::written(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::these(Unsupported::of('tautulli', 'lemonfiber cannot speak to it')),
        aConnectionEnded('A', HowAConnectionEnded::plainly(WhereAConnectionStands::Wired)),
        aConnectionEnded('B', HowAConnectionEnded::plainly(WhereAConnectionStands::AlreadyWired)),
        aConnectionEnded('C', HowAConnectionEnded::plainly(WhereAConnectionStands::Drifted)),
        aConnectionEnded('D', HowAConnectionEnded::plainly(WhereAConnectionStands::Stale)),
        AConnection::of('E', WhatItWouldBreak::warning('Downloads never arrive', 'Point Sonarr at /downloads again'), HowAConnectionEnded::conflicted('/downloads', '/mnt/downloads')),
        aConnectionEnded('F', HowAConnectionEnded::plainly(WhereAConnectionStands::Adopted)),
        aConnectionEnded('G', HowAConnectionEnded::plainly(WhereAConnectionStands::Unmanaged)),
        aConnectionEnded('H', HowAConnectionEnded::wouldWire('8989', '')),
        aConnectionEnded('I', HowAConnectionEnded::plainly(WhereAConnectionStands::WouldAdopt)),
        aConnectionEnded('J', HowAConnectionEnded::because(WhereAConnectionStands::Observed, 'You declared it unmanaged')),
        aConnectionEnded('K', HowAConnectionEnded::because(WhereAConnectionStands::Skipped, 'Radarr was not up yet')),
        aConnectionEnded('L', HowAConnectionEnded::failed('401 Unauthorized: the API key is wrong')),
        aConnectionEnded('M', HowAConnectionEnded::because(WhereAConnectionStands::Refused, 'Two arrs share one root folder')),
        aConnectionEnded('N', HowAConnectionEnded::because(WhereAConnectionStands::Unmatched, 'NZBGet names no adapter lemonfiber pairs with Sonarr')),
    );
}

/** The screen once a run was started and the stack answered with the work and then with this. */
function theScreenAfterWiring(AStackThatWires $wiring): HowTheServicesAreWired
{
    $screen = theWiringScreen($wiring);
    $screen->wire();
    $screen->going = null;

    return $screen;
}

/** The run the screen draws, which a test has arranged to be there. */
function theRunDrawn(HowTheServicesAreWired $screen): TheWiringAsShown
{
    return $screen->howItIsGoing()->wiring ?? throw new LogicException('no run was drawn');
}

it('opens on the services a run wires and the run to offer, and wires nothing', function (): void {
    $wiring = AStackThatWires::answering();
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theWiringScreen($wiring, supervising: $supervising);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->toContain(__('stacks.wiring.wire'))
        ->and($drawn->said())->toContain(__('stacks.wiring.what_a_run_does'))
        ->and($drawn->said())->toContain(__('stacks.wiring.services'))
        ->and(array_map(static fn(WhatOneServiceSays $service): string => $service->name, $screen->answer()->services))->toBe(['Sonarr', 'Jellyfin'])
        ->and($drawn->said())->toContain($screen->answer()->services[0]->name)
        ->and($drawn->said())->toContain(__($screen->answer()->services[0]->runsSaid))
        ->and($drawn->said())->not->toContain(__('stacks.wiring.no_services'))
        ->and($screen->howItIsGoing()->wiring)->toBeNull()
        ->and($wiring->asked())->toBe([])
        ->and($supervising->askings())->toBe(1);

    $screen->again();
    $screen->answer();

    expect($supervising->askings())->toBe(2)
        ->and($wiring->asked())->toBe([]);
});

it('says so where the machine runs nothing to wire', function (): void {
    $screen = theWiringScreen(AStackThatWires::answering(), supervising: AStackThatSupervises::withNothingRunning());

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.wiring.no_services'));
});

it('a machine that does not answer what it runs draws what stood in the way, and asks again for both', function (): void {
    $supervising = AStackThatSupervises::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')));
    $screen = theWiringScreen($wiring, supervising: $supervising);
    $screen->wire();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->toBe([__('health.ask_again')]);

    $screen->again();
    $screen->answer();
    $screen->howItIsGoing();

    expect($supervising->askings())->toBe(2)
        ->and($wiring->asked())->toBe(['wire', 'after:j-1']);
});

it('a session the stack refuses on opening signs this device out before anything is wired', function (): void {
    $keychain = AKeychainInMemory::working();
    $wiring = AStackThatWires::answering();
    $screen = theWiringScreen($wiring, keychain: $keychain, supervising: AStackThatSupervises::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($keychain->isHolding(theStackWhoseServicesAreWired()->id()))->toBeFalse()
        ->and($wiring->asked())->toBe([]);
});

it('starts a run, says it is working while it runs, and offers no second run meanwhile', function (): void {
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')));
    $screen = theWiringScreen($wiring);
    $screen->wire();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($wiring->asked())->toBe(['wire'])
        ->and($screen->following)->toBe('j-1')
        ->and($drawn->said())->toContain(__('stacks.wiring.working'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.wire'));
});

it('draws every connection in the state the stack gave it, skipped apart from failed and a changed value as kept', function (): void {
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $run = theRunDrawn($screen);

    expect(array_map(static fn(AConnectionAsShown $connection): string => $connection->stateSaid, $run->connections))->toBe([
        'stacks.wiring.state.wired',
        'stacks.wiring.state.already_wired',
        'stacks.wiring.state.drifted',
        'stacks.wiring.state.stale',
        'stacks.wiring.state.conflicted',
        'stacks.wiring.state.adopted',
        'stacks.wiring.state.unmanaged',
        'stacks.wiring.state.would_wire',
        'stacks.wiring.state.would_adopt',
        'stacks.wiring.state.observed',
        'stacks.wiring.state.skipped',
        'stacks.wiring.state.failed',
        'stacks.wiring.state.refused',
        'stacks.wiring.state.unmatched',
    ])->and(array_map(static fn(AConnectionAsShown $connection): string => $connection->connection, $run->connections))
        ->toBe(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.wiring.state.drifted'));
});

it('shows a service\'s rejection and every reason in the stack\'s own words', function (): void {
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $run = theRunDrawn($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect([$run->connections[9]->said, $run->connections[10]->said, $run->connections[11]->said, $run->connections[12]->said, $run->connections[13]->said])
        ->toBe(['You declared it unmanaged', 'Radarr was not up yet', '401 Unauthorized: the API key is wrong', 'Two arrs share one root folder', 'NZBGet names no adapter lemonfiber pairs with Sonarr'])
        ->and($run->connections[0]->said)->toBe('')
        ->and($drawn)->toContain('401 Unauthorized: the API key is wrong')
        ->and($drawn)->toContain('Radarr was not up yet');
});

it('shows what the service holds beside what lemonfiber would write, and offers nothing to put it back', function (): void {
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $run = theRunDrawn($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$run->connections[4]->ours, $run->connections[4]->yours])->toBe(['/downloads', '/mnt/downloads'])
        ->and([$run->connections[7]->ours, $run->connections[7]->yours])->toBe(['8989', ''])
        ->and($drawn->said())->toContain(__('stacks.wiring.yours', ['value' => '/mnt/downloads']))
        ->and($drawn->said())->toContain(__('stacks.wiring.ours', ['value' => '/downloads']))
        ->and($drawn->said())->toContain(__('stacks.wiring.ours', ['value' => '8989']))
        ->and($drawn->offers())->toBe([__('stacks.wiring.wire'), __('health.ask_again')]);
});

it('says what a warning breaks and what puts it right, and nothing of the kind where nothing breaks', function (): void {
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $run = theRunDrawn($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect([$run->connections[4]->breakage, $run->connections[4]->remediation])->toBe(['Downloads never arrive', 'Point Sonarr at /downloads again'])
        ->and([$run->connections[0]->breakage, $run->connections[0]->remediation])->toBe(['', ''])
        ->and($drawn)->toContain(__('stacks.wiring.breaks', ['breakage' => 'Downloads never arrive']))
        ->and($drawn)->toContain(__('stacks.wiring.remedy', ['remediation' => 'Point Sonarr at /downloads again']));
});

it('says whether drift could be judged, and that a run which could not is its own answer', function (): void {
    $unjudged = TheWiring::written(HowDriftWasJudged::Unassessable, WhatIsUnsupported::none(), aConnectionEnded('A', HowAConnectionEnded::plainly(WhereAConnectionStands::AlreadyWired)));
    $judged = theRunDrawn(theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection()))));
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered($unjudged)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    $said = array_search(__('stacks.wiring.unassessable'), $drawn, strict: true);
    $first = array_search('A', $drawn, strict: true);

    expect($judged->judgedSaid)->toBe('stacks.wiring.assessed')
        ->and(theRunDrawn($screen)->judgedSaid)->toBe('stacks.wiring.unassessable')
        ->and(is_int($said) && is_int($first) && $said < $first)->toBeTrue();
});

it('labels a rehearsed run as one before anything else, and a run that wrote as nothing of the kind', function (): void {
    $rehearsed = TheWiring::rehearsed(HowDriftWasJudged::Assessed, WhatIsUnsupported::none(), aConnectionEnded('A', HowAConnectionEnded::wouldWire('8989', '8990')));
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered($rehearsed)));
    $written = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    $label = array_search(__('stacks.wiring.rehearsed'), $drawn, strict: true);
    $judged = array_search(__('stacks.wiring.assessed'), $drawn, strict: true);

    expect(theRunDrawn($screen)->rehearsed)->toBeTrue()
        ->and(theRunDrawn($written)->rehearsed)->toBeFalse()
        ->and(is_int($label) && is_int($judged) && $label < $judged)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($written)->said())->not->toContain(__('stacks.wiring.rehearsed'));
});

it('names what a run cannot wire, with why, and says so where it can wire everything', function (): void {
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection())));
    $empty = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(TheWiring::written(HowDriftWasJudged::Assessed, WhatIsUnsupported::none()))));

    expect(theRunDrawn($screen)->unsupported[0]->with)->toBe(['what' => 'tautulli', 'because' => 'lemonfiber cannot speak to it'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.already_here.unsupported', ['what' => 'tautulli', 'because' => 'lemonfiber cannot speak to it']))
        ->and(WhatTheDeviceWouldDraw::by($empty)->said())->toContain(__('stacks.wiring.nothing_unsupported'))
        ->and(WhatTheDeviceWouldDraw::by($empty)->said())->toContain(__('stacks.wiring.no_connections'));
});

it('shows a request the stack turned down in its own words', function (): void {
    $screen = theWiringScreen(AStackThatWires::answering(WhatBecameOfTheWiring::refused('Nothing here to wire')));
    $screen->wire();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->howItIsGoing()->refusal)->toBe('Nothing here to wire')
        ->and($screen->following)->toBeNull()
        ->and($drawn->said())->toContain(__('stacks.wiring.refused'))
        ->and($drawn->said())->toContain('Nothing here to wire')
        ->and($drawn->offers())->toBe([__('stacks.wiring.wire'), __('health.ask_again')]);

    $screen->again();

    expect($screen->howItIsGoing()->refusal)->toBe('Nothing here to wire');
});

it('says the stack has no outcome for a run it no longer knows, which is not a refusal', function (): void {
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')));
    $screen = theScreenAfterWiring($wiring);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.wiring.no_outcome'))
        ->and($screen->howItIsGoing()->hasEnded)->toBeTrue()
        ->and($screen->following)->toBeNull()
        ->and($wiring->asked())->toBe(['wire', 'after:j-1']);
});

it('asks after a run only while it runs', function (): void {
    $wiring = AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('j-1')),
        WhatBecameOfTheWiring::underway(Job::named('j-1')),
        WhatBecameOfTheWiring::answered(aRunOfEveryConnection()),
    );
    $screen = theWiringScreen($wiring);
    $screen->wire();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    expect($wiring->asked())->toBe(['wire', 'after:j-1', 'after:j-1']);
});

it('starts a second run afresh, letting go of the one it followed', function (): void {
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection()));
    $screen = theWiringScreen($wiring);
    $screen->wire();
    $screen->wire();

    expect($wiring->asked())->toBe(['wire', 'wire'])
        ->and($screen->following)->toBeNull();
});

it('a run the stack could not be reached for is an obstacle that can be asked again', function (): void {
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), WhatBecameOfTheWiring::underway(Job::named('j-1')));
    $screen = theScreenAfterWiring($wiring);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->howItIsGoing()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->toContain(__('health.ask_again'));

    $screen->again();
    $screen->howItIsGoing();

    expect($wiring->asked())->toBe(['wire', 'after:j-1', 'after:j-1']);
});

it('asking again reads the services afresh and keeps the report a run came to', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered(aRunOfEveryConnection()));
    $screen = theWiringScreen($wiring, supervising: $supervising);
    $screen->wire();
    $screen->going = null;
    $screen->answer();
    $screen->howItIsGoing();
    $screen->again();
    $screen->answer();

    expect(theRunDrawn($screen)->connections)->toHaveCount(14)
        ->and($supervising->askings())->toBe(2)
        ->and($wiring->asked())->toBe(['wire', 'after:j-1']);
});

it('asking again before anything was drawn reads the services, and starts nothing', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $wiring = AStackThatWires::answering();
    $screen = theWiringScreen($wiring, supervising: $supervising);
    $screen->again();

    expect($screen->going)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('stacks.wiring.wire'), __('health.ask_again')])
        ->and($supervising->askings())->toBe(1)
        ->and($wiring->asked())->toBe([]);
});

it('asking again lets go of an obstacle met starting a run, and does not start one', function (): void {
    $wiring = AStackThatWires::answering(WhatBecameOfTheWiring::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = theWiringScreen($wiring);
    $screen->wire();

    expect($screen->howItIsGoing()->went->cameBack())->toBeFalse();

    $screen->again();

    expect($screen->howItIsGoing()->went->cameBack())->toBeTrue()
        ->and($screen->howItIsGoing()->wiring)->toBeNull()
        ->and($wiring->asked())->toBe(['wire']);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theWiringScreen(AStackThatWires::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), keychain: $keychain);
    $screen->wire();

    expect($screen->howItIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseServicesAreWired()->id()))->toBeFalse();
});

it('a session that has ended asks the stack nothing', function (): void {
    $wiring = AStackThatWires::answering();
    $screen = theWiringScreen($wiring, signedIn: false);
    $screen->wire();

    expect($screen->howItIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($wiring->asked())->toBe([]);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theWiringScreen(AStackThatWires::answering());
    $screen->setParams(['stack' => 7]);

    expect(static fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theWiringScreen(AStackThatWires::answering());

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve(TheMenu::Connections->screen()->forTheStack($screen->stack()->id())))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theWiringScreen(AStackThatWires::answering())->render()->name())->toBe('operator::how-the-services-are-wired');
});

it('draws an area the operator declared unmanaged as left alone in their words, never as a change, with no value and nothing to do about it', function (): void {
    $leftAlone = TheWiring::written(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::none(),
        aConnectionEnded('Sonarr to qBittorrent', HowAConnectionEnded::because(WhereAConnectionStands::Observed, 'Tuned by hand for the seedbox')),
    );
    $screen = theScreenAfterWiring(AStackThatWires::answering(WhatBecameOfTheWiring::underway(Job::named('j-1')), WhatBecameOfTheWiring::answered($leftAlone)));
    $shown = theRunDrawn($screen)->connections[0];
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$shown->stateSaid, $shown->said, $shown->ours, $shown->yours])->toBe(['stacks.wiring.state.observed', 'Tuned by hand for the seedbox', '', ''])
        ->and($drawn->said())->toContain(__('stacks.wiring.state.observed'), 'Tuned by hand for the seedbox')
        ->and($drawn->said())->not->toContain(__('stacks.wiring.state.drifted'))
        ->and($drawn->said())->not->toContain(__('stacks.wiring.state.failed'))
        ->and($drawn->said())->not->toContain(__('stacks.wiring.state.skipped'))
        ->and($drawn->offers())->toBe([__('stacks.wiring.wire'), __('health.ask_again')]);
});

/** A link asking for a capability, each claimant with where it came from. */
function aLinkAskingFor(string $by, string $capability, Services $reached, WhatSettledIt $settled, AClaimant ...$claimants): ALink
{
    return ALink::from(ServiceId::called($by), HowItReaches::asked(Capability::called($capability), $reached, $settled, TheClaimants::these(...$claimants)));
}

/** What a stack wires to what, one link of every kind. */
function aStackWiringOneOfEach(): TheLinks
{
    $bundled = static fn(string $service): AClaimant => AClaimant::of(ServiceId::called($service), WhoPutItThere::bundled());

    return TheLinks::of(
        WhatNothingFills::these(Unfilled::of(ServiceId::called('lidarr'), Capability::called('music-tagger'))),
        aLinkAskingFor('sonarr', 'download-client', Services::these(ServiceId::called('qbittorrent')), WhatSettledIt::outright(), $bundled('qbittorrent')),
        aLinkAskingFor('prowlarr', 'arr', Services::these(ServiceId::called('sonarr'), ServiceId::called('radarr')), WhatSettledIt::each(), $bundled('sonarr'), $bundled('radarr')),
        aLinkAskingFor('seerr', 'media-server', Services::none(), WhatSettledIt::contested(Services::these(ServiceId::called('plex'), ServiceId::called('jellyfin'))), $bundled('jellyfin'), AClaimant::of(ServiceId::called('plex'), WhoPutItThere::plugin('plex'))),
        aLinkAskingFor('radarr', 'indexer', Services::these(ServiceId::called('prowlarr')), WhatSettledIt::chosen(Services::these(ServiceId::called('jackett')), WhoSettledIt::Operator, WhyItWasChosen::stated('Jackett is too slow here')), $bundled('prowlarr'), $bundled('jackett')),
        aLinkAskingFor('bazarr', 'subtitles', Services::these(ServiceId::called('opensubtitles')), WhatSettledIt::chosen(Services::these(ServiceId::called('subscene')), WhoSettledIt::Stack, WhyItWasChosen::unstated())),
        aLinkAskingFor('lidarr', 'music-tagger', Services::none(), WhatSettledIt::unfilled()),
        ALink::from(ServiceId::called('jellyfin'), HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs on the other machine')),
    );
}

it('opens on what answers what, above the services and the run, in the stack\'s words and order', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::with(aStackWiringOneOfEach())))->said();
    $at = static fn(mixed $line): int => (int) array_search($line, $drawn, strict: true);

    expect($drawn)->toContain(
        __('stacks.wiring.fills.asks', ['by' => 'sonarr', 'capability' => 'download-client']),
        __('stacks.wiring.fills.outright', ['service' => 'qbittorrent']),
        __('stacks.wiring.fills.each', ['services' => 'sonarr, radarr']),
        __('stacks.wiring.fills.unfilled'),
        __('stacks.wiring.fills.by_name', ['service' => 'tdarr']),
        __('stacks.wiring.fills.why', ['why' => 'Transcoding runs on the other machine']),
        __(WhoSetIt::Bundled->ofAService()),
        __('stacks.wiring.fills.label'),
        __('stacks.wiring.services'),
    )->and($at(__('stacks.wiring.fills.label')))->toBeLessThan($at(__('stacks.wiring.services')))
        ->and($at(__('stacks.wiring.fills.asks', ['by' => 'sonarr', 'capability' => 'download-client'])))
        ->toBeLessThan($at(__('stacks.wiring.fills.asks', ['by' => 'prowlarr', 'capability' => 'arr'])))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.none'))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.unreadable'));
});

it('tells a choice the operator made from one the stack made, with the reason where one was given', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::with(aStackWiringOneOfEach())))->said();

    expect($drawn)->toContain(
        __('stacks.wiring.fills.chosen_operator', ['service' => 'prowlarr', 'over' => 'jackett']),
        __('stacks.wiring.fills.why', ['why' => 'Jackett is too slow here']),
        __('stacks.wiring.fills.chosen_stack', ['service' => 'opensubtitles', 'over' => 'subscene']),
    )->and($drawn)->not->toContain(__('stacks.wiring.fills.chosen_operator', ['service' => 'opensubtitles', 'over' => 'subscene']))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.chosen_stack', ['service' => 'prowlarr', 'over' => 'jackett']));
});

it('draws a contest as one, naming every claimant in the contest\'s order with where it came from, and offers no choice', function (): void {
    $screen = theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::with(aStackWiringOneOfEach()));
    $contest = $screen->whatAnswersWhat()->links[2];
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($contest->isContested)->toBeTrue()
        ->and(array_map(static fn(AClaimantAsShown $claimant): string => $claimant->name, $contest->claimants))->toBe(['plex', 'jellyfin'])
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.contested'), __('stacks.wiring.fills.claimed_by'), __(WhoSetIt::Plugin->ofAService(), ['named' => 'plex']))
        ->and($drawn->offers())->toBe([__('stacks.wiring.wire'), __('health.ask_again')]);
});

it('says the stack asks nothing of its services where it wires nothing, and is not a wiring it could not read', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::with(TheLinks::of(WhatNothingFills::none()))))->said();

    expect($drawn)->toContain(__('stacks.wiring.fills.none'))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.unreadable'));
});

it('says a wiring the stack could not read could not be read, in the stack\'s words, and never draws it as settled', function (): void {
    $why = ARefusalInItsWords::said('The record of what is installed cannot be read', 'Nothing can be said about what fills what until it reads.', WhatTheRefusalNamed::as('/var/lib/lemonfiber/plugins.json'));
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::refusing($why)))->said();

    expect($drawn)->toContain(__('stacks.wiring.fills.unreadable'), 'The record of what is installed cannot be read', 'Nothing can be said about what fills what until it reads.')
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.none'))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.unfilled'));
});

it('says what answers what could not be read where the stack was not reached, and still draws the services', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen(AStackThatWires::answering(), linking: AStackThatSaysWhatAnswersWhat::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->said();

    expect($drawn)->toContain(__('stacks.wiring.fills.unreadable'), __('stacks.wiring.services'))
        ->and($drawn)->not->toContain(__('stacks.wiring.fills.none'));
});

it('asks what answers what once a frame, afresh when asked again, and keeps none of it between screens', function (): void {
    $linking = AStackThatSaysWhatAnswersWhat::with(aStackWiringOneOfEach());
    $screen = theWiringScreen(AStackThatWires::answering(), linking: $linking);

    $screen->whatAnswersWhat();
    $screen->whatAnswersWhat();
    $once = $linking->askings();
    $screen->again();
    $screen->whatAnswersWhat();
    theWiringScreen(AStackThatWires::answering(), linking: $linking)->whatAnswersWhat();

    expect($once)->toBe(1)
        ->and($linking->askings())->toBe(3)
        ->and($linking->askedAbout()?->id()->stored())->toBe(theStackWhoseServicesAreWired()->id()->stored());
});

it('asks nothing of a stack this device holds no session for', function (): void {
    $linking = AStackThatSaysWhatAnswersWhat::with(aStackWiringOneOfEach());
    $screen = theWiringScreen(AStackThatWires::answering(), signedIn: false, linking: $linking);

    expect($screen->whatAnswersWhat()->went->isSignedIn)->toBeFalse()
        ->and($linking->askings())->toBe(0);
});
