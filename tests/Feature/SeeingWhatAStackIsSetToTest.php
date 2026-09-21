<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\StacksInMemory;

// Everything a machine is set to, on one screen.
//
// The screen's whole claim is that what is drawn is what came back, so what is
// worth asserting is not that it renders — it is that it renders the listing
// unchanged: the stack's order, the stack's rows, and the stack's own note
// where a value was withheld.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose settings this screen is about. */
function theStackWhoseSettingsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * Three settings, in an order no sort would produce.
 *
 * Deliberately not alphabetical and deliberately mixed: a listing of one kind
 * cannot catch a fold that treats a withheld value like a shown one, and a
 * listing already in order cannot catch a screen that sorts.
 */
function whatTheLoftIsSetTo(): Settings
{
    return Settings::of(
        Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media')),
        Setting::called('API_KEY', WhatASettingHolds::withheld('set, not shown')),
        Setting::called('BIND', WhatASettingHolds::shown('lan')),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. */
function theSettingsScreen(
    AStackThatIsSet $arranging,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatThisStackIsSetTo {
    $stack = theStackWhoseSettingsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisStackIsSetTo($arranging, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('shows every setting the stack sent, in the order it sent them', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    expect($screen->answer()->howMany)->toBe(3)
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe('');

    // The order is the stack's. Alphabetical would put API_KEY first, so this
    // fails the day somebody sorts on the way through.
    $rows = $screen->answer()->set;

    expect($rows[0]->key)->toBe('LIBRARY_PATH')
        ->and($rows[1]->key)->toBe('API_KEY')
        ->and($rows[2]->key)->toBe('BIND');
});

it('keeps a withheld value apart from a shown one', function (): void {
    // The assertion the whole fold exists for. Both arrive as strings, and a
    // screen that lost the difference would print a credential's note where a
    // value belongs — or a value where the note belongs, which is the one that
    // matters.
    $rows = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()))->answer()->set;

    expect($rows[0]->withheld)->toBeFalse()
        ->and($rows[0]->said)->toBe('/data/media')
        ->and($rows[1]->withheld)->toBeTrue()
        ->and($rows[1]->said)->toBe('set, not shown');
});

it('says a stack holds nothing rather than drawing a blank', function (): void {
    // Not the same screen as a stack that could not be asked, and they arrive
    // as the same absence. The count is what the template branches on.
    $screen = theSettingsScreen(AStackThatIsSet::toNothing());

    expect($screen->answer()->howMany)->toBe(0)
        ->and($screen->answer()->set)->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeTrue();
});

it('reports what stood in the way rather than an empty listing', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::StackDidNotAnswer));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->answer()->howMany)->toBe(0);
});

it('asks the stack it is on, once, however many rows are drawn', function (): void {
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging);

    $screen->answer();
    $screen->answer();

    expect($arranging->askings())->toBe(1)
        ->and($arranging->askedAbout()?->id()->is(theStackWhoseSettingsAreRead()->id()))->toBeTrue();
});

it('asks again when the operator asks it to', function (): void {
    // The held answer is dropped rather than re-read, so the next thing that
    // wants it does the asking — one path to the stack instead of two that can
    // disagree about what happened.
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($arranging->askings())->toBe(2);
});

it('shows the sign-in prompt rather than a listing when the session has gone', function (): void {
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->howMany)->toBe(0)
        // Never asked. A screen that reached the stack with no session would
        // spend a round trip to be told what it already knew.
        ->and($arranging->askings())->toBe(0);
});

it('reads a route parameter that is not text as naming no machine', function (): void {
    // The narrowing on the way out of the router, whose parameter array is
    // untyped. Anything that is not a string names no stack, which is the same
    // situation as a route with nothing in that segment. Every screen that
    // reads a stack out of the route makes this assertion, because every one
    // of them has the same branch — and a branch nothing drives is a branch
    // that can quietly become the other one.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N3-R13 — lets the session go when the stack refuses the credential', function (): void {
    // The listing is a read, and a read refused on the credential is the same
    // signed-out device as one refused anywhere else: the identity was
    // removed, the password changed, or the stack was rebuilt. A session left
    // in the store is resumed on the next frame and refused again, and the
    // operator ends up at a sign-in prompt on a device that still believes it
    // is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseSettingsAreRead()->id()))->toBeFalse();
});

it('N3-R13 — keeps the session when the machine could not be reached', function (): void {
    // The other half, and the reason the release is a decision rather than a
    // reflex. A phone in flight mode has not lost its pairing, and forgetting
    // the session here would make somebody sign in again to read settings they
    // were entitled to read all along.
    $keychain = AKeychainInMemory::working();
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::DeviceHasNoNetwork), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($keychain->isHolding(theStackWhoseSettingsAreRead()->id()))->toBeTrue();
});

it('names where it goes and what it draws', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    expect($screen->goes()->settings())->toContain('/settings')
        ->and($screen->render()->name())->toBe('operator::what-this-stack-is-set-to');
});
