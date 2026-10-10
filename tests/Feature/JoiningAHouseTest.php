<?php

declare(strict_types=1);

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Operator\Internal\Screens\JoiningAHouse;
use Modules\Operator\Internal\WhatFindingTheHouseMet;
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
use Tests\Support\WhatTheRouterHolds;

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
        $stacks,
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
            __('household.joining.your_invitation'),
            __('household.joining.your_invitation_explained'),
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
        ->and($screen->met)->toBe(WhatFindingTheHouseMet::CodeUnreadable)
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

    expect($screen->met)->toBe(WhatFindingTheHouseMet::NotKept)
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

it('is reached from the first screen', function (): void {
    expect(NativeRouter::resolve(AScreenWithoutAStack::JoiningAHouse->value))->not->toBeNull();
});

/**
 * An invitation's join link to the house, with whatever a test needs to change about it.
 *
 * @param array<string, string> $changed
 */
function theJoinLink(array $changed = []): string
{
    return sprintf('lemonfiber://join?%s', http_build_query([
        'address' => 'https://192.168.1.42:8443',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        'stack' => THE_HOUSE,
        'expires' => '2000',
        'name' => 'Robin Ash',
        ...$changed,
    ], encoding_type: PHP_QUERY_RFC3986));
}

/** The house as a phone already holds it, under the certificate given. */
function theHouseHeld(string $fingerprint): Stack
{
    return Stack::of(StackId::saidBy(THE_HOUSE), StackName::of('The loft'), Address::of('https://192.168.1.42:8443'), Fingerprint::of($fingerprint));
}

it('adds the house a scanned join link names, pinned to its certificate, and asks the person to sign in as the name it carries', function (): void {
    $stacks = StacksInMemory::working();
    $screen = theWayIn(ACameraInMemory::reading(theJoinLink()), stacks: $stacks);

    $screen->findTheHouse();

    $kept = $stacks->configured()->stack(StackId::rememberedAs(THE_HOUSE));

    expect($screen->at)->toBe(WhereTheWayInIs::SigningIn)
        ->and($screen->met)->toBeNull()
        ->and($screen->theirName)->toBe('Robin Ash')
        ->and($kept->name()->shown())->toBe(__('household.joining.called'))
        ->and($kept->presents()->is(Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS))))->toBeTrue();
});

it('opens the join link the phone opened it at, straight to signing in', function (): void {
    $screen = theWayIn(ACameraInMemory::reading(''));
    WhatTheRouterHolds::over($screen, sprintf('%s?%s', AScreenWithoutAStack::JoiningAHouse->value, (string) parse_url(theJoinLink(), PHP_URL_QUERY)), AScreenWithoutAStack::TheList->value);

    $screen->mount();

    expect($screen->at)->toBe(WhereTheWayInIs::SigningIn)
        ->and($screen->theirName)->toBe('Robin Ash')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('onboarding.step', ['step' => 2, 'of' => 3]));
});

it('opens on finding the house where the phone opened it at no link', function (): void {
    $screen = theWayIn(ACameraInMemory::reading(''));
    WhatTheRouterHolds::over($screen, AScreenWithoutAStack::JoiningAHouse->value, AScreenWithoutAStack::TheList->value);

    $screen->mount();

    expect($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and($screen->met)->toBeNull();
});

it('goes straight to signing in on a phone that holds the house under the same certificate, and changes nothing it holds', function (): void {
    $stacks = StacksInMemory::holding(theHouseHeld(str_repeat('a', Fingerprint::CHARACTERS)));
    $screen = theWayIn(ACameraInMemory::reading(theJoinLink()), stacks: $stacks);

    $screen->findTheHouse();

    expect($screen->at)->toBe(WhereTheWayInIs::SigningIn)
        ->and($stacks->configured()->stack(StackId::rememberedAs(THE_HOUSE))->name()->shown())->toBe('The loft');
});

it('refuses a join link for a house the phone holds under another certificate, and never re-pins it', function (): void {
    $stacks = StacksInMemory::holding(theHouseHeld(str_repeat('b', Fingerprint::CHARACTERS)));
    $screen = theWayIn(ACameraInMemory::reading(theJoinLink()), stacks: $stacks);

    $screen->findTheHouse();

    expect($screen->at)->toBe(WhereTheWayInIs::FindingTheHouse)
        ->and($screen->met)->toBe(WhatFindingTheHouseMet::NotThisHouse)
        ->and($stacks->configured()->stack(StackId::rememberedAs(THE_HOUSE))->presents()->is(Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS))))->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.joining.not_this_house'), __('household.joining.not_this_house_action'));
});

it('refuses a join link it cannot use, in household words, and keeps nothing', function (string $link): void {
    $stacks = StacksInMemory::working();
    $screen = theWayIn(ACameraInMemory::reading($link), stacks: $stacks);

    $screen->findTheHouse();

    expect($screen->met)->toBe(WhatFindingTheHouseMet::LinkUnusable)
        ->and($stacks->holdsAny())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.joining.link_unusable'), __('household.joining.link_unusable_action'));
})->with([
    'lapsed' => [theJoinLink(['expires' => '1000'])],
    'with a parameter it does not know' => [theJoinLink(['operator' => 'yes'])],
    'without a name' => ['lemonfiber://join?address=https%3A%2F%2F192.168.1.42%3A8443'],
    'carrying a claim, which this app cannot offer yet' => [theJoinLink(['claim' => 'a-token-of-enough-random-bits'])],
]);

it('is opened by the platform at the scheme every join link is written under', function (): void {
    expect(config('nativephp.deeplink_scheme'))->toBe(AJoinLink::scheme());
});
