<?php

declare(strict_types=1);

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Operator\Internal\Screens\JoiningAHouse;
use Modules\Operator\Internal\WhatStoodInTheWayOfJoining;
use Modules\Operator\Internal\WhereTheWayInIs;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\ADoorThatWasKnockedOn;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The moment every code in this file is scanned at. */
const JOINED_AT = 1_000;

/** The house every code in this file names, by its identifier. */
const THE_HOUSE = '7f3c9a1e5b2d4086a9c1e3f5b7d90246';

/** The code whoever runs the house shows for a new phone. */
function theCodeForANewPhone(): string
{
    return (string) json_encode([
        'address' => 'https://192.168.1.42',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        'expires' => 2_000,
        'stack' => THE_HOUSE,
    ]);
}

/** The member's way in, with a camera, a door and a store that answer as a test says. */
function theWayIn(
    ACameraInMemory $camera,
    ?ADoorThatWasKnockedOn $door = null,
    ?StacksInMemory $stacks = null,
    ?AKeychainInMemory $keychain = null,
): JoiningAHouse {
    $stacks ??= StacksInMemory::working();
    $keychain ??= AKeychainInMemory::working();
    $clock = FrozenClock::at(Instant::atEpochSeconds(JOINED_AT));

    return new JoiningAHouse(
        $camera,
        new Introducing(),
        new Remembering($stacks, $keychain),
        $clock,
        $door ?? ADoorThatWasKnockedOn::openingFor(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(JOINED_AT * 2), Whose::member('a-member')),
        $keychain,
        AroundThePhone::holding($stacks, storage: $keychain, clock: $clock),
        app('translator'),
        new AppsSettingsThatOpen(),
    );
}

/** The way in, past the code, with a name and a password typed. */
function signingInAs(JoiningAHouse $screen, string $name = 'Robin', string $password = 'a-members-password'): JoiningAHouse
{
    $screen->findTheHouse();
    $screen->__syncProperty('theirName', $name);
    $screen->__syncProperty('typed', $password);

    return $screen;
}

it('opens on finding the house, saying it is the first of three steps', function (): void {
    $screen = theWayIn(ACameraInMemory::reading(theCodeForANewPhone()));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and($said)->toContain(
            __('onboarding.step', ['step' => 1, 'of' => 3]),
            __('household.joining.the_code_for_a_new_phone'),
            __('household.joining.the_code_for_a_new_phone_explained'),
            __('household.joining.scan'),
        );
});

it('keeps the house a scanned code names under the household name, and moves on to signing in', function (): void {
    $stacks = StacksInMemory::working();
    $screen = theWayIn(ACameraInMemory::reading(theCodeForANewPhone()), stacks: $stacks);

    $screen->findTheHouse();

    $kept = $stacks->configured();

    expect($screen->at)->toBe(WhereTheWayInIs::SigningIn)
        ->and($screen->joined)->toBe(THE_HOUSE)
        ->and($kept->stack(StackId::rememberedAs(THE_HOUSE))->name()->shown())->toBe(__('household.joining.called'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
            __('onboarding.step', ['step' => 2, 'of' => 3]),
            __('household.joining.your_name'),
            __('household.joining.your_password'),
        );
});

it('stays on the first step and says so when what was scanned is not a code for a new phone', function (): void {
    $stacks = StacksInMemory::working();
    $screen = theWayIn(ACameraInMemory::reading('https://example.org/not-a-code'), stacks: $stacks);

    $screen->findTheHouse();

    expect($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and($screen->codeWasUnreadable)->toBeTrue()
        ->and($stacks->holdsAny())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.joining.code_unreadable'), __('household.joining.code_unreadable_action'));
});

it('says why the camera gave nothing back, with the way to put it right', function (): void {
    $screen = theWayIn(ACameraInMemory::answering(WhyNothingWasScanned::TheCameraWasDeclined));

    $screen->findTheHouse();

    expect($screen->nothingWasScanned())->toBeTrue()
        ->and($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(WhyNothingWasScanned::TheCameraWasDeclined->saidOnTheScreen()), __('household.joining.the_camera_was_declined_action'));
});

it('says what to do about every way the camera can give nothing back, in household words, in every language', function (): void {
    foreach (WhyNothingWasScanned::cases() as $why) {
        $screen = theWayIn(ACameraInMemory::answering($why));
        $screen->findTheHouse();

        foreach (['en', 'nl'] as $locale) {
            expect(app('translator')->has($screen->whatToDoAboutTheCamera(), $locale, fallback: false))->toBeTrue($why->value);
        }
    }

    expect(theWayIn(ACameraInMemory::reading(theCodeForANewPhone()))->whatToDoAboutTheCamera())->toBe('');
});

it('stays on the first step when this phone could not keep the house', function (): void {
    $screen = theWayIn(ACameraInMemory::reading(theCodeForANewPhone()), stacks: StacksInMemory::refusing(WhyAStackCannotBeRemembered::StoreWouldNotOpen));

    $screen->findTheHouse();

    expect($screen->notKept)->toBeTrue()
        ->and($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.joining.not_kept'), __('household.joining.not_kept_action'));
});

it('signs in with the name and password given, keeps the session and lands on Home', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = signingInAs(theWayIn(ACameraInMemory::reading(theCodeForANewPhone()), keychain: $keychain));

    $screen->signIn();

    $home = AStacksScreen::Shelf->forTheStack(StackId::rememberedAs(THE_HOUSE));

    expect($screen->went)->toBe(HowTheSignInWent::SignedIn)
        ->and($screen->typed)->toBe('')
        ->and($keychain->isHolding(StackId::rememberedAs(THE_HOUSE)))->toBeTrue()
        ->and($screen->getNavigationIntent()?->type)->toBe(NavigationIntent::RESET)
        ->and($screen->getNavigationIntent()?->uri)->toBe($home)
        ->and(NativeRouter::resolve($home))->not->toBeNull();
});

it('offers nothing to the door until a name is typed', function (): void {
    $screen = signingInAs(theWayIn(ACameraInMemory::reading(theCodeForANewPhone())), name: '   ');

    $screen->signIn();

    expect($screen->went)->toBe(HowTheSignInWent::NotYet)
        ->and($screen->getNavigationIntent())->toBeNull();
});

it('says a name and password that did not match in the household words, and stays', function (): void {
    $screen = signingInAs(theWayIn(ACameraInMemory::reading(theCodeForANewPhone()), ADoorThatWasKnockedOn::refusing(Obstacle::of(KindOfObstacle::CredentialWasRefused))));

    $screen->signIn();

    expect($screen->stoodInTheWay())->toBe(WhatStoodInTheWayOfJoining::Refused)
        ->and($screen->getNavigationIntent())->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.joining.refused'), __('household.joining.refused_action'));
});

it('says a house out of reach as every member screen says it', function (): void {
    $screen = signingInAs(theWayIn(ACameraInMemory::reading(theCodeForANewPhone()), ADoorThatWasKnockedOn::refusing(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    $screen->signIn();

    expect($screen->stoodInTheWay())->toBe(WhatStoodInTheWayOfJoining::NoAnswer)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.out_of_reach.no_answer'), __('household.out_of_reach.no_answer_action'));
});

it('says nothing stood in the way before signing in, or once it worked', function (): void {
    expect(WhatStoodInTheWayOfJoining::of(HowTheSignInWent::NotYet))->toBeNull()
        ->and(WhatStoodInTheWayOfJoining::of(HowTheSignInWent::SignedIn))->toBeNull();
});

it('reads every way a sign-in can go wrong as what stood in the way of joining', function (HowTheSignInWent $went, WhatStoodInTheWayOfJoining $stood): void {
    expect(WhatStoodInTheWayOfJoining::of($went))->toBe($stood);
})->with([
    'a password refused' => [HowTheSignInWent::CredentialWasRefused, WhatStoodInTheWayOfJoining::Refused],
    'a pair refused' => [HowTheSignInWent::ThePairWasRefused, WhatStoodInTheWayOfJoining::Refused],
    'too many attempts' => [HowTheSignInWent::TooManyAttempts, WhatStoodInTheWayOfJoining::TooManyAttempts],
    'nowhere to keep a session' => [HowTheSignInWent::NoStoreOnThisDevice, WhatStoodInTheWayOfJoining::NotKept],
    'a store that would not open' => [HowTheSignInWent::TheStoreWouldNotOpen, WhatStoodInTheWayOfJoining::NotKept],
    'the local network not allowed' => [HowTheSignInWent::TheNetworkIsNotPermitted, WhatStoodInTheWayOfJoining::NotPermitted],
    'another certificate' => [HowTheSignInWent::TheMachineIsNotTheOnePaired, WhatStoodInTheWayOfJoining::NotTheHouse],
    'an address that is not the house' => [HowTheSignInWent::TheAddressIsNotTheStacks, WhatStoodInTheWayOfJoining::NotTheHouse],
    'no answer' => [HowTheSignInWent::StackDidNotAnswer, WhatStoodInTheWayOfJoining::NoAnswer],
    'a name not found' => [HowTheSignInWent::NameWasNotFound, WhatStoodInTheWayOfJoining::NameNotFound],
    'nothing at the address' => [HowTheSignInWent::NothingAtThePairedAddress, WhatStoodInTheWayOfJoining::NothingAtTheAddress],
    'a connection turned away' => [HowTheSignInWent::ConnectionWasTurnedAway, WhatStoodInTheWayOfJoining::ConnectionRefused],
    'an answer that could not be read' => [HowTheSignInWent::AnswerCouldNotBeRead, WhatStoodInTheWayOfJoining::AnswerUnreadable],
]);

it('is reached from the first screen', function (): void {
    expect(NativeRouter::resolve(AScreenWithoutAStack::JoiningAHouse->value))->not->toBeNull();
});
