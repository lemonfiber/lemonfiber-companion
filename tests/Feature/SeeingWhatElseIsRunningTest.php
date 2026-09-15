<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingElseRunning;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatTheEngineCallsIt;
use Modules\Operator\Internal\Screens\WhatElseIsRunningHere;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;

// N2-R21 — what is running here that this stack never declared.
//
// Reachable, named, and saying what each is running — and never shown as part
// of the stack or offered a verb. The last of those is not asserted here
// because it is not a fact about this screen: the value the reader hands over
// carries no identifier of the kind a verb accepts, so there is nothing for a
// template to wire. What a test can say is that the rows carry the two things
// the requirement asks for.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose undeclared containers this screen is about. */
function theStackWhoseStrangersAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** What the three verbs cost on the machine this file is about. */
function whatTheVerbsCostBesideTheStrangers(): Disturbances
{
    return Disturbances::of(
        starting: WhatItTakesAway::atMost(180),
        stopping: WhatItTakesAway::atMost(10),
        restarting: WhatItTakesAway::atMost(180),
    );
}

/** Two containers the machine is running that nobody declared. */
function twoThingsNobodyDeclared(): WhatElseIsRunning
{
    return WhatElseIsRunning::these(
        SomethingElseRunning::called(
            id: WhatTheEngineCallsIt::called('pihole'),
            describes: 'Not declared by this stack',
            runs: HowAServiceRuns::Running,
        ),
        SomethingElseRunning::called(
            id: WhatTheEngineCallsIt::called('watchtower'),
            describes: 'Not declared by this stack',
            runs: HowAServiceRuns::Healthy,
        ),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. */
function theStrangersScreen(
    AStackThatSupervises $supervising,
    bool $signedIn = true,
): WhatElseIsRunningHere {
    $stack = theStackWhoseStrangersAreRead();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatElseIsRunningHere($supervising, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N2-R21 — names each one and says what it is running', function (): void {
    $screen = theStrangersScreen(AStackThatSupervises::alsoRunning(
        Daemons::none(whatTheVerbsCostBesideTheStrangers()),
        twoThingsNobodyDeclared(),
    ));

    expect($screen->howMany())->toBe(2)
        ->and($screen->answer()->isSignedIn)->toBeTrue()
        ->and($screen->answer()->met)->toBe('')
        ->and($screen->answer()->remedy)->toBe('');

    $rows = $screen->answer()->running;

    expect($rows[0]->named)->toBe('pihole')
        ->and($rows[0]->describes)->toBe('Not declared by this stack')
        ->and($rows[0]->runs)->toBe(HowAServiceRuns::Running->saidOnTheScreen())
        ->and($rows[1]->named)->toBe('watchtower')
        ->and($rows[1]->runs)->toBe(HowAServiceRuns::Healthy->saidOnTheScreen());
});

it('N2-R21 — a machine running only what it declared says so', function (): void {
    // The ordinary answer, and it is not the same screen as a machine that
    // could not be asked. Nothing unaccounted for is what an operator wants to
    // read, and an empty list with no sentence reads as nobody having looked.
    $screen = theStrangersScreen(AStackThatSupervises::with(
        Daemons::none(whatTheVerbsCostBesideTheStrangers()),
    ));

    expect($screen->howMany())->toBe(0)
        ->and($screen->answer()->isSignedIn)->toBeTrue()
        ->and($screen->answer()->met)->toBe('');
});

it('N2-R21 — a machine that could not be asked reports no strangers, not none', function (): void {
    // The obstacle branch. What matters is that the empty list arrives beside
    // the obstacle rather than instead of it: a screen that showed *nothing
    // else is running here* over an unreachable machine would be answering a
    // question nobody got to ask.
    $screen = theStrangersScreen(AStackThatSupervises::met(Obstacle::StackDidNotAnswer));

    expect($screen->answer()->met)->not->toBe('')
        ->and($screen->answer()->running)->toBe([])
        ->and($screen->howMany())->toBe(0);
});

it('N1-R44 — a session that has ended is not a machine running nothing', function (): void {
    $screen = theStrangersScreen(
        AStackThatSupervises::with(Daemons::none(whatTheVerbsCostBesideTheStrangers())),
        signedIn: false,
    );

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and($screen->answer()->met)->toBe('')
        ->and($screen->howMany())->toBe(0);
});
