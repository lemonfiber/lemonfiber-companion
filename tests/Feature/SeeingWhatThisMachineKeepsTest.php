<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingBeside;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisMachineKeepsHere;
use Modules\Operator\Internal\ViewModels\ARootAsShown;
use Modules\Operator\Internal\ViewModels\SomethingBesideAsShown;
use Modules\Operator\Internal\ViewModels\SomethingKeptAsShown;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Storekeepers;
use Native\Mobile\Edge\NativeRouter;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatListsItsCopies;
use Tests\Support\Fakes\AStackThatSaysWhatItKeeps;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What this machine keeps, where, and why, and the copies it holds.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine this screen is about. */
function theStackWhoseKeepingIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A machine keeping one secret and one thing that is not, beside a library that is not its own. */
function whatTheLoftKeeps(): WhatThisMachineKeeps
{
    return WhatThisMachineKeeps::of(
        TheRoots::of(WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes')),
        WhatIsKept::of(
            SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret),
            SomethingKept::kept('Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain),
        ),
        WhatIsBeside::of(SomethingBeside::named('/srv/media', 'Your library, which the stack reads and never removes')),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theKeepingScreen(
    Storing $storing,
    AStackThatListsItsCopies $copying,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatThisMachineKeepsHere {
    $stack = theStackWhoseKeepingIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisMachineKeepsHere($storing, $copying, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** Two copies, newest first, as the stack lists them. */
function twoCopies(): AStackThatListsItsCopies
{
    return AStackThatListsItsCopies::with(TheCopies::named('lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full'));
}

it('N6-R7 — shows where things are kept, what, why, and whether each holds a secret', function (): void {
    $answer = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies())->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and(array_map(static fn(ARootAsShown $r): array => [$r->where, $r->what], $answer->roots))->toBe([['/srv/lemonfiber', 'Everything the stack writes']])
        ->and(array_map(static fn(SomethingKeptAsShown $k): array => [$k->what, $k->where, $k->why, $k->secretSaid], $answer->kept))->toBe([
            ['The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret->saidOnTheScreen()],
            ['Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain->saidOnTheScreen()],
        ])
        ->and(array_map(static fn(SomethingBesideAsShown $b): array => [$b->what, $b->why], $answer->beside))->toBe([['/srv/media', 'Your library, which the stack reads and never removes']])
        ->and($answer->copies->went->cameBack())->toBeTrue()
        ->and($answer->copies->names)->toBe(['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full']);
});

it('N6-R7 — a value the stack sends beside a secret never reaches the glass', function (): void {
    // The real adapter, not the fake, because the fake cannot carry a value
    // at all and this is about what happens when the wire does.
    //
    // `stored` is stood in for and not judged: the `value` beside the secret
    // is one the contract does not declare, put there to show that a field
    // the reader does not name never reaches the glass.
    $leaked = 'hunter2-not-for-the-screen';
    $said = [
        'api_version' => 1,
        'kind' => 'stored',
        'data' => [
            'roots' => [],
            'kept' => [['what' => 'The VPN credentials', 'at' => '/srv/lemonfiber/secrets/vpn', 'why' => 'So the tunnel can be raised', 'secret' => true, 'value' => $leaked]],
            'beside' => [],
            'removal' => ['state' => 'not-asked'],
        ],
    ];
    // Destroyed first, because `MockClient::global()` is `??=` and every
    // test begins with one that answers nothing.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($said))]);

    $drawn = WhatTheDeviceWouldDraw::by(theKeepingScreen(new Storekeepers(new PinnedClients()), twoCopies()))->said();

    expect($drawn)->toContain('The VPN credentials')
        ->and($drawn)->toContain(__(WhetherItHoldsASecret::Secret->saidOnTheScreen()))
        ->and(implode("\n", $drawn))->not->toContain($leaked);
});

it('N6-R9 — no copy taken and a list that could not be read are different sentences on the glass', function (): void {
    $none = WhatTheDeviceWouldDraw::by(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::with(TheCopies::named())))->said();
    $unread = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::StackDidNotAnswer));
    $unreadDrawn = WhatTheDeviceWouldDraw::by($unread)->said();

    expect($none)->toContain(__('stacks.keeps.no_copies'))
        ->and($none)->not->toContain(__('stacks.keeps.copies_unread'))
        ->and($unreadDrawn)->toContain(__('stacks.keeps.copies_unread'))
        ->and($unreadDrawn)->toContain(__(Obstacle::StackDidNotAnswer->said()))
        ->and($unreadDrawn)->toContain(__(Obstacle::StackDidNotAnswer->remedy()))
        ->and(WhatTheDeviceWouldDraw::by($unread)->offers())->toBe([__('health.ask_again')])
        ->and($unreadDrawn)->not->toContain(__('stacks.keeps.no_copies'))
        // What the stack keeps is still shown: the copies failing is theirs alone.
        ->and($unread->answer()->went->cameBack())->toBeTrue()
        ->and($unreadDrawn)->toContain('The VPN credentials');
});

it('N6-R9 — the copies drawn are the copies listed, each by its name', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies()))->said();

    expect($drawn)->toContain('lemonfiber-20260924-0300-full')
        ->and($drawn)->toContain('lemonfiber-20260923-0300-full')
        ->and($drawn)->not->toContain(__('stacks.keeps.no_copies'));
});

it('says so where the stack keeps nothing, names nowhere, and has nothing beside it', function (): void {
    $empty = WhatThisMachineKeeps::of(TheRoots::of(), WhatIsKept::of(), WhatIsBeside::of());
    $drawn = WhatTheDeviceWouldDraw::by(theKeepingScreen(AStackThatSaysWhatItKeeps::with($empty), twoCopies()))->said();

    expect($drawn)->toContain(__('stacks.keeps.no_roots'))
        ->and($drawn)->toContain(__('stacks.keeps.nothing_kept'))
        ->and($drawn)->toContain(__('stacks.keeps.nothing_beside'));
});

it('N6-R11 — offers asking again and nothing else, least of all a first run', function (): void {
    $offers = WhatTheDeviceWouldDraw::by(theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies()))->offers();

    expect($offers)->toBe([__('health.ask_again')]);
});

it('a stack that could not be asked is not a machine keeping nothing, and its copies are not asked', function (): void {
    $copying = twoCopies();
    $answer = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::StackDidNotAnswer), $copying)->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->roots)->toBe([])
        ->and($answer->kept)->toBe([])
        ->and($answer->beside)->toBe([])
        ->and($answer->copies->names)->toBe([])
        ->and($answer->copies->went->cameBack())->toBeFalse()
        ->and($copying->askings())->toBe(0);
});

it('N1-R44 — a session that has ended is not a machine keeping nothing', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $answer = theKeepingScreen($storing, twoCopies(), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->kept)->toBe([])
        ->and($storing->askings())->toBe(0);
});

it('N3-R13 — a credential refused for what is kept signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::CredentialWasRefused), twoCopies(), $keychain);

    expect($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeFalse();
});

it('N3-R13 — a credential refused for the copies lets the session go as well', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->copies->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('N1-R3 — copies that could not be read for another reason leave the session standing', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), AStackThatListsItsCopies::met(Obstacle::StackDidNotAnswer), $keychain);

    expect($screen->answer()->copies->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->copies->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($keychain->isHolding(theStackWhoseKeepingIsRead()->id()))->toBeTrue();
});

it('each reading is asked once for a frame, about the machine the route names', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $copying = twoCopies();
    $screen = theKeepingScreen($storing, $copying);

    $screen->answer();
    $screen->answer();

    expect($storing->askings())->toBe(1)
        ->and($copying->askings())->toBe(1)
        ->and($storing->wasGivenASession())->toBeTrue()
        ->and($copying->wasGivenASession())->toBeTrue()
        ->and($storing->askedAbout()?->id()->stored())->toBe(theStackWhoseKeepingIsRead()->id()->stored())
        ->and($copying->askedAbout()?->id()->stored())->toBe(theStackWhoseKeepingIsRead()->id()->stored());
});

it('N1-R3 — asking again asks both again', function (): void {
    $storing = AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps());
    $copying = AStackThatListsItsCopies::met(Obstacle::DeviceHasNoNetwork);
    $screen = theKeepingScreen($storing, $copying);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($storing->askings())->toBe(2)
        ->and($copying->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::met(Obstacle::DeviceHasNoNetwork), twoCopies());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies());

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->keeps()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theKeepingScreen(AStackThatSaysWhatItKeeps::with(whatTheLoftKeeps()), twoCopies());

    expect($screen->render()->name())->toBe('operator::what-this-machine-keeps-here');
});
