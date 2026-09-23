<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\WhetherItIsHeard;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatYouAreToldAbout;
use Modules\Operator\Internal\ViewModels\OneEventSetApart;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSaysWhatItTells;
use Tests\Support\Fakes\StacksInMemory;

// What this machine will tell its operator about.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose alert setting this screen is about. */
function theStackWhoseAlertsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A quiet preset with one event kept quiet and one heard anyway. */
function aQuietSettingWithTwoExceptions(): WhatTheOperatorIsTold
{
    return WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of(
        AnEventSetApart::of('update-available', WhetherItIsHeard::Silenced),
        AnEventSetApart::of('disk-low', WhetherItIsHeard::Heard),
    ));
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theToldScreen(
    AStackThatSaysWhatItTells $telling,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatYouAreToldAbout {
    $stack = theStackWhoseAlertsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatYouAreToldAbout($telling, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N10-R8 — shows the preset with what it means and with every exception made to it', function (): void {
    $answer = theToldScreen(AStackThatSaysWhatItTells::with(aQuietSettingWithTwoExceptions()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->preset)->toBe('quiet')
        ->and($answer->means)->toBe('Only what needs you today')
        ->and(array_map(static fn(OneEventSetApart $event): array => [$event->kind, $event->heardSaid], $answer->exceptions))->toBe([
            ['update-available', WhetherItIsHeard::Silenced->saidOnTheScreen()],
            ['disk-low', WhetherItIsHeard::Heard->saidOnTheScreen()],
        ]);
});

it('a preset with nothing set apart is an answer', function (): void {
    $answer = theToldScreen(AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('everything', 'Every event, as it happens', SetApart::of())))->answer();

    expect($answer->went->cameBack())->toBeTrue()->and($answer->exceptions)->toBe([]);
});

it('N10-R12 — a stack that could not be asked is not a setting of nothing', function (): void {
    $answer = theToldScreen(AStackThatSaysWhatItTells::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->preset)->toBe('')
        ->and($answer->means)->toBe('')
        ->and($answer->exceptions)->toBe([]);
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    $answer = theToldScreen(AStackThatSaysWhatItTells::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R44 — a session that has ended is not a setting of nothing', function (): void {
    $answer = theToldScreen(AStackThatSaysWhatItTells::with(aQuietSettingWithTwoExceptions()), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->preset)->toBe('')
        ->and($answer->means)->toBe('')
        ->and($answer->exceptions)->toBe([]);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theToldScreen(AStackThatSaysWhatItTells::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseAlertsAreRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseAlertsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $telling = AStackThatSaysWhatItTells::with(aQuietSettingWithTwoExceptions());
    $screen = theToldScreen($telling);

    $screen->answer();
    $screen->answer();

    expect($telling->askings())->toBe(1)
        ->and($telling->wasGivenASession())->toBeTrue()
        ->and($telling->askedAbout()?->id()->stored())->toBe(theStackWhoseAlertsAreRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $telling = AStackThatSaysWhatItTells::met(Obstacle::DeviceHasNoNetwork);
    $screen = theToldScreen($telling);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($telling->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theToldScreen(AStackThatSaysWhatItTells::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theToldScreen(AStackThatSaysWhatItTells::with(aQuietSettingWithTwoExceptions()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theToldScreen(AStackThatSaysWhatItTells::with(aQuietSettingWithTwoExceptions()));

    expect($screen->render()->name())->toBe('operator::what-you-are-told-about');
});
