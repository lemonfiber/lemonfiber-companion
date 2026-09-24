<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowWhatLeavesReads;
use Modules\Operator\Internal\Screens\WhatLeavesHere;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSaysWhatLeavesIt;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Everything that leaves this machine, in two lists.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose connections this screen is about. */
function theStackWhoseConnectionsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two of lemonfiber's requests and three services, one of each kind of answer. */
function aMachineThatSendsThings(): WhatLeavesThisMachine
{
    return WhatLeavesThisMachine::of(
        OurRequests::of(
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Registry, WhereItGoes::to('ghcr.io', 'lscr.io'), 'To fetch the images the stack runs', 'The name and version of each image', WhetherItIsAllowed::Allowed, 'registry.pull', 'No service can be installed or updated'),
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Updates, WhereItGoes::to(), 'To say when a newer lemonfiber is out', 'Nothing but the request itself', WhetherItIsAllowed::SwitchedOff, 'updates.check', 'Nobody hears that a version came out'),
        ),
        TheirRequests::of(
            ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata', WhoPutItThere::bundled()),
            ARequestOfTheirs::recorded(ServiceId::called('gluetun'), '', 'Nothing of its own', WhoPutItThere::bundled()),
            ARequestOfTheirs::unrecorded(ServiceId::called('my-fork'), WhoPutItThere::bundled()),
        ),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theLeavingScreen(
    AStackThatSaysWhatLeavesIt $outgoing,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatLeavesHere {
    $stack = theStackWhoseConnectionsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatLeavesHere($outgoing, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N10-R1 — lemonfiber\'s requests and the services\' come back as two lists', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->ours)->toHaveCount(2)
        ->and($answer->theirs)->toHaveCount(3);
});

it('N10-R2, N10-R3 — each of lemonfiber\'s requests says why, what it sends, where, its switch and what turning it off costs', function (): void {
    $registry = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->ours[0];

    expect($registry->asksForSaid)->toBe(WhatLemonfiberAsksFor::Registry->saidOnTheScreen())
        ->and($registry->purpose)->toBe('To fetch the images the stack runs')
        ->and($registry->sends)->toBe('The name and version of each image')
        ->and($registry->destinations)->toBe(['ghcr.io', 'lscr.io'])
        ->and($registry->allowedSaid)->toBe(WhetherItIsAllowed::Allowed->saidOnTheScreen())
        ->and($registry->switch)->toBe('registry.pull')
        ->and($registry->cost)->toBe('No service can be installed or updated');
});

it('a request configured to reach nowhere is not a request switched off, and each is said', function (): void {
    $updates = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->ours[1];

    expect($updates->destinations)->toBe([])
        ->and($updates->allowedSaid)->toBe(WhetherItIsAllowed::SwitchedOff->saidOnTheScreen());
});

it('a service says where it goes, that it goes nowhere, or that nobody knows — three sentences, never two', function (): void {
    [$sonarr, $gluetun, $fork] = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()))->answer()->theirs;

    expect([$sonarr->service, $sonarr->reachesSaid, $sonarr->destination, $sonarr->purpose])->toBe(['sonarr', HowWhatLeavesReads::REACHES, 'thetvdb.com', 'Series metadata'])
        ->and([$gluetun->service, $gluetun->reachesSaid, $gluetun->destination, $gluetun->purpose])->toBe(['gluetun', HowWhatLeavesReads::REACHES_NOTHING, '', 'Nothing of its own'])
        ->and([$fork->service, $fork->reachesSaid, $fork->destination, $fork->purpose])->toBe(['my-fork', HowWhatLeavesReads::UNRECORDED, '', '']);
});

it('N10-R12 — a machine sending nothing is an answer rather than a gap', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(WhatLeavesThisMachine::of(OurRequests::of(), TheirRequests::of())))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->ours)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('N10-R12 — a stack that could not be asked is not a machine sending nothing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->ours)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R44 — a session that has ended is not a machine sending nothing', function (): void {
    $answer = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->ours)->toBe([])
        ->and($answer->theirs)->toBe([]);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseConnectionsAreRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseConnectionsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $outgoing = AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings());
    $screen = theLeavingScreen($outgoing);

    $screen->answer();
    $screen->answer();

    expect($outgoing->askings())->toBe(1)
        ->and($outgoing->wasGivenASession())->toBeTrue()
        ->and($outgoing->askedAbout()?->id()->stored())->toBe(theStackWhoseConnectionsAreRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $outgoing = AStackThatSaysWhatLeavesIt::met(Obstacle::DeviceHasNoNetwork);
    $screen = theLeavingScreen($outgoing);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($outgoing->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineThatSendsThings()));

    expect($screen->render()->name())->toBe('operator::what-leaves-here');
});

/**
 * A machine whose services include a plugin's, after one of the stack's own.
 *
 * The stack's own first, so a screen deciding from the first row alone gets
 * this wrong; the plugin's service unrecorded, which is how one arrives.
 */
function aMachineWithAPluginsService(WhoPutItThere $second): WhatLeavesThisMachine
{
    return WhatLeavesThisMachine::of(
        OurRequests::of(),
        TheirRequests::of(
            ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata', WhoPutItThere::bundled()),
            ARequestOfTheirs::unrecorded(ServiceId::called('plex'), $second),
        ),
    );
}

it('F7-R9 — a plugin\'s service says who brought it, and the list says once what an unmarked row is', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::plugin('plex'))));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->marksAnOrigin())->toBeTrue()
        ->and($screen->answer()->theirs[0]->from->isTheStacksOwn())->toBeTrue()
        ->and($screen->answer()->theirs[1]->from->attributed)->toBe('plex')
        ->and($drawn)->toContain(__('stacks.outbound.theirs.origin.plugin', ['named' => 'plex']))
        ->and($drawn)->toContain(__('stacks.outbound.theirs.origin.legend'))
        ->and($drawn)->not->toContain(__('stacks.outbound.theirs.origin.bundled'));
});

it('F7-R11 — a service nobody could attribute is marked with the stack\'s reason', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::unknown('its plugin was removed'))));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('stacks.outbound.theirs.origin.unknown', ['why' => 'its plugin was removed']));
});

it('a list of only the stack\'s own services marks none and explains nothing', function (): void {
    $screen = theLeavingScreen(AStackThatSaysWhatLeavesIt::with(aMachineWithAPluginsService(WhoPutItThere::bundled())));

    expect($screen->answer()->marksAnOrigin())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('stacks.outbound.theirs.origin.legend'));
});
