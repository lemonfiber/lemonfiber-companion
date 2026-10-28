<?php

declare(strict_types=1);

use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowTheServicesAreWired;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatChoosesFillers;
use Tests\Support\Fakes\AStackThatSaysWhatAnswersWhat;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatWires;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The screen whose services are wired, on a stack it knows, signed in. Named for this file (`G10`). */
function theScreenThatAdopts(AStackThatWires $wiring): HowTheServicesAreWired
{
    $stack = Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowTheServicesAreWired($wiring, AStackThatSaysWhatAnswersWhat::with(TheLinks::of(WhatNothingFills::none())), AStackThatChoosesFillers::answering(WhatBecameOfTheFill::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

function whatAdoptingWouldDo(): TheWiring
{
    return TheWiring::rehearsed(
        HowDriftWasJudged::Assessed,
        AConnection::of('Sonarr into Jellyfin', WhatItWouldBreak::nothing(), HowAConnectionEnded::plainly(WhereAConnectionStands::WouldAdopt)),
        AConnection::of('SABnzbd into Sonarr', WhatItWouldBreak::nothing(), HowAConnectionEnded::wouldWire('http://sabnzbd:8080', '')),
    );
}

function whatAdoptingDid(): TheWiring
{
    return TheWiring::written(
        HowDriftWasJudged::Assessed,
        AConnection::of('Sonarr into Jellyfin', WhatItWouldBreak::nothing(), HowAConnectionEnded::plainly(WhereAConnectionStands::Adopted)),
    );
}

function aStackThatAdopts(): AStackThatWires
{
    return AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('a-rehearsal')),
        WhatBecameOfTheWiring::answered(whatAdoptingWouldDo()),
        WhatBecameOfTheWiring::underway(Job::named('an-adoption')),
        WhatBecameOfTheWiring::answered(whatAdoptingDid()),
    );
}

it('offers seeing what adopting the operator\'s edits would do, and adopts nothing yet', function (): void {
    $wiring = aStackThatAdopts();
    $drawn = WhatTheDeviceWouldDraw::by(theScreenThatAdopts($wiring));

    expect($drawn->said())->toContain(__('stacks.wiring.what_adopting_does'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.rehearse_adopting'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.adopt'))
        ->and($wiring->asked())->toBe([]);
});

it('rehearses adopting, and draws each value kept as theirs and each connection it would wire, labelled as a rehearsal', function (): void {
    $wiring = aStackThatAdopts();
    $screen = theScreenThatAdopts($wiring);
    $screen->rehearseAdopting();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($wiring->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal'])
        ->and($drawn->said())->toContain(__('stacks.wiring.rehearsed'), 'Sonarr into Jellyfin', __('stacks.wiring.state.would_adopt'), 'SABnzbd into Sonarr', __('stacks.wiring.state.would_wire'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.adopt'));
});

it('adopts only against that rehearsal, and draws an adopted value as the operator\'s rather than as drift', function (): void {
    $wiring = aStackThatAdopts();
    $screen = theScreenThatAdopts($wiring);
    $screen->adopt();

    expect($wiring->asked())->toBe([]);

    $screen->rehearseAdopting();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->adopt();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($wiring->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal', 'adopt', 'after:an-adoption'])
        ->and($drawn->said())->toContain(__('stacks.wiring.state.adopted'))
        ->and($drawn->said())->not->toContain(__('stacks.wiring.state.drifted'), __('stacks.wiring.rehearsed'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.adopt'));
});

it('sends no yes while the rehearsal of adopting is still being asked, and sends the yes once', function (): void {
    $wiring = aStackThatAdopts();
    $screen = theScreenThatAdopts($wiring);
    $screen->rehearseAdopting();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->rehearseAdopting();
    $screen->adopt();

    expect($wiring->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal', 'rehearse-adopting']);

    $again = aStackThatAdopts();
    $twice = theScreenThatAdopts($again);
    $twice->rehearseAdopting();
    $twice->whileItRuns();
    $twice->howItIsGoing();
    $twice->adopt();
    $twice->adopt();

    expect($again->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal', 'adopt']);
});

it('offers no adopting yes after a wiring run sent while adopting was being rehearsed', function (): void {
    $wiring = AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('a-rehearsal')),
        WhatBecameOfTheWiring::underway(Job::named('a-run')),
        WhatBecameOfTheWiring::answered(whatAdoptingWouldDo()),
    );
    $screen = theScreenThatAdopts($wiring);
    $screen->rehearseAdopting();
    $screen->wire();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->adopt();

    expect($wiring->asked())->toBe(['rehearse-adopting', 'wire', 'after:a-run']);
});

it('offers no adopting yes after a wiring run, even one that only rehearsed', function (): void {
    $wiring = AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('a-run')),
        WhatBecameOfTheWiring::answered(whatAdoptingWouldDo()),
    );
    $screen = theScreenThatAdopts($wiring);
    $screen->wire();
    $screen->whileItRuns();
    $screen->adopt();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.wiring.adopt'))
        ->and($wiring->asked())->toBe(['wire', 'after:a-run']);
});
