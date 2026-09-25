<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AServiceBeside;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheDoorCameToBe;
use Modules\Kernel\Api\HowTheDoorWasChosen;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\TheServicesBeside;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHouseholdBegins;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhereTheHouseholdComesIn;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackWithAFrontDoor;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The household's front door, and what else they can reach.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about, at an address no line on the screen may be built from. */
function theStackWhoseDoorIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A door the operator named and the stack refused, with a service beside it that has no address. */
function aDoorWhoseNamingWasRefused(): TheFrontDoor
{
    return TheFrontDoor::reported(
        WhereTheFrontDoorStands::Established,
        'Send people to Jellyseerr',
        HowTheDoorCameToBe::refused('homepage', 'It lists every service'),
        WhereTheHouseholdBegins::at('Jellyseerr', WhatItFaces::Asking, AnAddressToHand::at('http://loft.local:5055', 'Only on the home network')),
        TheServicesBeside::of(
            AServiceBeside::said('Jellyfin', WhatItFaces::Watching, 'Nothing can be asked for there', AnAddressToHand::at('http://loft.local:8096', 'Changes if the router restarts')),
            AServiceBeside::said('Homepage', WhatItFaces::Operators, 'It shows services the house should not see', AnAddressToHand::none()),
        ),
    );
}

/** A door with a given standing and choice, and nothing at it or beside it. */
function aDoorWithNothingAtIt(WhereTheFrontDoorStands $standing, HowTheDoorCameToBe $chosen): TheFrontDoor
{
    return TheFrontDoor::reported($standing, 'Nothing here is published to the household', $chosen, WhereTheHouseholdBegins::nowhere(), TheServicesBeside::of());
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theDoorScreen(
    AStackWithAFrontDoor $welcoming,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhereTheHouseholdComesIn {
    $stack = theStackWhoseDoorIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhereTheHouseholdComesIn($welcoming, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('says where the door stands, what that means, and where they begin', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused())))->said();

    expect($drawn)->toContain(__(WhereTheFrontDoorStands::Established->saidOnTheScreen()))
        ->and($drawn)->toContain('Send people to Jellyseerr')
        ->and($drawn)->toContain('Jellyseerr')
        ->and($drawn)->toContain(__(WhatItFaces::Asking->saidOnTheScreen()));
});

it('draws every address exactly as the stack sent it, with its caution, and composes none', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused())))->said();

    expect($drawn)->toContain('http://loft.local:5055')
        ->and($drawn)->toContain('Only on the home network')
        ->and($drawn)->toContain('http://loft.local:8096')
        ->and($drawn)->toContain('Changes if the router restarts')
        ->and(implode("\n", $drawn))->not->toContain('192.168.1.42');
});

it('says what each service beside the door is to the household, and why it is not the door', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused())))->said();

    expect($drawn)->toContain(__('stacks.front_door.beside'))
        ->and($drawn)->toContain('Jellyfin')
        ->and($drawn)->toContain(__(WhatItFaces::Watching->saidOnTheScreen()))
        ->and($drawn)->toContain('Nothing can be asked for there')
        ->and($drawn)->toContain('Homepage')
        ->and($drawn)->toContain(__(WhatItFaces::Operators->saidOnTheScreen()))
        ->and($drawn)->toContain('It shows services the house should not see')
        ->and($drawn)->toContain(__('stacks.front_door.no_address'));
});

it('says a named door was refused, what was named and why', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused())))->said();

    expect($drawn)->toContain(__(HowTheDoorWasChosen::Refused->saidOnTheScreen(), ['named' => 'homepage', 'because' => 'It lists every service']));
});

it('says a door was worked out rather than chosen, and a chosen one was chosen', function (): void {
    $derived = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWithNothingAtIt(WhereTheFrontDoorStands::None, HowTheDoorCameToBe::derived()))))->said();
    $named = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with(aDoorWithNothingAtIt(WhereTheFrontDoorStands::Stranded, HowTheDoorCameToBe::byTheOperator('jellyseerr')))))->said();

    expect($derived)->toContain(__(HowTheDoorWasChosen::Derived->saidOnTheScreen()))
        ->and($derived)->toContain(__(WhereTheFrontDoorStands::None->saidOnTheScreen()))
        ->and($named)->toContain(__(HowTheDoorWasChosen::Named->saidOnTheScreen(), ['named' => 'jellyseerr']))
        ->and($named)->toContain(__(WhereTheFrontDoorStands::Stranded->saidOnTheScreen()));
});

it('a stack with nothing open to the household says so, rather than drawing a door', function (): void {
    $screen = theDoorScreen(AStackWithAFrontDoor::with(aDoorWithNothingAtIt(WhereTheFrontDoorStands::None, HowTheDoorCameToBe::derived())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('Nothing here is published to the household')
        ->and($drawn)->toContain(__('stacks.front_door.nothing_beside'))
        ->and($screen->answer()->begins->service)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('health.ask_again')]);
});

it('a door with no address says the machine did not say where it is reached', function (): void {
    $door = TheFrontDoor::reported(WhereTheFrontDoorStands::Stranded, 'It answers and cannot be found', HowTheDoorCameToBe::derived(), WhereTheHouseholdBegins::at('Jellyseerr', WhatItFaces::Asking, AnAddressToHand::none()), TheServicesBeside::of());
    $drawn = WhatTheDeviceWouldDraw::by(theDoorScreen(AStackWithAFrontDoor::with($door)))->said();

    expect($drawn)->toContain('Jellyseerr')
        ->and($drawn)->toContain(__('stacks.front_door.no_address'));
});

it('a stack that could not be asked is not a stack with no door', function (): void {
    $screen = theDoorScreen(AStackWithAFrontDoor::met(Obstacle::StackDidNotAnswer));
    $answer = $screen->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and([
            $answer->standingSaid, $answer->meaning, $answer->chosenSaid, $answer->named, $answer->refusal,
            $answer->begins->service, $answer->begins->facingSaid, $answer->begins->url, $answer->begins->caution, $answer->beside,
        ])->toBe(['', '', '', '', '', '', '', '', '', []])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__(WhereTheFrontDoorStands::None->saidOnTheScreen()));
});

it('a session that has ended is not a stack with no door', function (): void {
    $welcoming = AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused());
    $answer = theDoorScreen($welcoming, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->beside)->toBe([])
        ->and($welcoming->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theDoorScreen(AStackWithAFrontDoor::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseDoorIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $welcoming = AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused());
    $screen = theDoorScreen($welcoming);

    $screen->answer();
    $screen->answer();

    expect($welcoming->askings())->toBe(1)
        ->and($welcoming->wasGivenASession())->toBeTrue()
        ->and($welcoming->askedAbout()?->id()->stored())->toBe(theStackWhoseDoorIsRead()->id()->stored());
});

it('asking again asks the machine again', function (): void {
    $welcoming = AStackWithAFrontDoor::met(Obstacle::DeviceHasNoNetwork);
    $screen = theDoorScreen($welcoming);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($welcoming->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theDoorScreen(AStackWithAFrontDoor::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->frontDoor()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theDoorScreen(AStackWithAFrontDoor::with(aDoorWhoseNamingWasRefused()))->render()->name())->toBe('operator::where-the-household-comes-in');
});
