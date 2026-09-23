<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhereItComesFrom;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhereThisComesFrom;
use Modules\Operator\Internal\ViewModels\WhereOneServiceComesFrom;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatNamesItsOrigins;
use Tests\Support\Fakes\StacksInMemory;

// Where every service on this machine comes from.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose origins this screen is about. */
function theStackWhoseOriginsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two services, one of them under a licence nobody would call unusual. */
function twoServicesAndWhereTheyComeFrom(): WhereTheServicesComeFrom
{
    return WhereTheServicesComeFrom::declaring(
        WhereItComesFrom::declared(ServiceId::called('sonarr'), 'Sonarr', 'lscr.io/linuxserver/sonarr', '4.0.15', 'https://github.com/Sonarr/Sonarr', 'GPL-3.0-only'),
        WhereItComesFrom::declared(ServiceId::called('gluetun'), 'Gluetun', 'qmcgaw/gluetun', 'v3.40.0', 'https://github.com/qdm12/gluetun', 'MIT'),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theOriginsScreen(
    AStackThatNamesItsOrigins $provenance,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhereThisComesFrom {
    $stack = theStackWhoseOriginsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhereThisComesFrom($provenance, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N11-R6 — each service says what it runs, what it is pinned at, where it is built from and under what licence', function (): void {
    $answer = theOriginsScreen(AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->services)->toHaveCount(2);

    $sonarr = $answer->services[0];

    expect($sonarr->name)->toBe('Sonarr')
        ->and($sonarr->image)->toBe('lscr.io/linuxserver/sonarr')
        ->and($sonarr->pinned)->toBe('4.0.15')
        ->and($sonarr->upstream)->toBe('https://github.com/Sonarr/Sonarr')
        ->and($sonarr->licence)->toBe('GPL-3.0-only');
});

it('N11-R7 — every service carries its licence, the unremarkable ones too', function (): void {
    $services = theOriginsScreen(AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()))->answer()->services;

    expect(array_map(static fn(WhereOneServiceComesFrom $row): string => $row->licence, $services))->toBe(['GPL-3.0-only', 'MIT']);
});

it('keeps the order the stack declares its services in', function (): void {
    $services = theOriginsScreen(AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()))->answer()->services;

    expect(array_map(static fn(WhereOneServiceComesFrom $row): string => $row->name, $services))->toBe(['Sonarr', 'Gluetun']);
});

it('N11-R8 — the pin and the licence come from the stack alone, with nothing else asked', function (): void {
    // The screen's only collaborator that reaches anything is the port, and it
    // is asked once — so no upstream stands between an operator and the pin or
    // the licence, and one that is unreachable changes neither.
    $provenance = AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom());
    $answer = theOriginsScreen($provenance)->answer();

    expect($provenance->askings())->toBe(1)
        ->and($answer->services[1]->pinned)->toBe('v3.40.0')
        ->and($answer->services[1]->licence)->toBe('MIT');
});

it('a machine declaring nothing is an answer rather than a gap', function (): void {
    $answer = theOriginsScreen(AStackThatNamesItsOrigins::with(WhereTheServicesComeFrom::declaring()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->services)->toBe([]);
});

it('a stack that could not be asked is not a machine declaring nothing', function (): void {
    $answer = theOriginsScreen(AStackThatNamesItsOrigins::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->services)->toBe([]);
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    $answer = theOriginsScreen(AStackThatNamesItsOrigins::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R44 — a session that has ended is not a machine declaring nothing', function (): void {
    $answer = theOriginsScreen(
        AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()),
        signedIn: false,
    )->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->services)->toBe([]);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theOriginsScreen(AStackThatNamesItsOrigins::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseOriginsAreRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseOriginsAreRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $provenance = AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom());
    $screen = theOriginsScreen($provenance);

    $screen->answer();
    $screen->answer();

    expect($provenance->askings())->toBe(1)
        ->and($provenance->wasGivenASession())->toBeTrue()
        ->and($provenance->askedAbout()?->id()->stored())->toBe(theStackWhoseOriginsAreRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $provenance = AStackThatNamesItsOrigins::met(Obstacle::DeviceHasNoNetwork);
    $screen = theOriginsScreen($provenance);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($provenance->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theOriginsScreen(AStackThatNamesItsOrigins::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theOriginsScreen(AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theOriginsScreen(AStackThatNamesItsOrigins::with(twoServicesAndWhereTheyComeFrom()));

    expect($screen->render()->name())->toBe('operator::where-this-comes-from');
});
