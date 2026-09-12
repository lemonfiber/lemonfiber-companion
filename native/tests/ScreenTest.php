<?php

declare(strict_types=1);

use Lemonfiber\Native\Screen;
use Native\Mobile\Testing\FakeBridge;

// The plugin's PHP face, driven through the real bridge call.
//
// `FakeBridge` is `nativephp/mobile`'s own seam: it binds into the container and
// intercepts `nativephp_call()` in-process. Using it rather than an interface of
// our own means every assertion here goes through the method name, the JSON
// encoding and the decoding of the answer — the whole path — instead of through
// something built to resemble it.
//
// The bridge names are written out as literals here, and that is deliberate
// rather than an oversight. `Screen` reaches them through `Call`, so a test
// spelling them `Call::Conceal->value` would agree with a wrong enum and prove
// nothing — the literal is the test stating the wire name for itself. `CallTest`
// holds the enum against `nativephp.json` separately, which is the pair that
// keeps the Kotlin and the Swift in the conversation.
//
// What the native half decides is not re-litigated here. `CaptureRule` carries
// that, in Kotlin and in Swift, with the same six cases each. This file is about
// the three things the PHP can get wrong on its own: which function it calls,
// how it reads an answer, and what it does with one it cannot read.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('asks the bridge function the manifest declares', function (): void {
    // The names have to match `nativephp.json` exactly or the call lands
    // nowhere — and a bridge that does not recognise a name answers like a
    // bridge with no device, which decodes to "unprotected" and looks exactly
    // like a window that simply is not protected.
    $bridge = FakeBridge::enable()
        ->respondTo('Lemonfiber.Conceal', ['protected' => true])
        ->respondTo('Lemonfiber.Reveal', ['protected' => false])
        ->respondTo('Lemonfiber.IsProtected', ['protected' => true]);

    $window = new Screen();

    $window->conceal();
    $window->reveal();
    $window->isProtected();

    $bridge->assertCalled('Lemonfiber.Conceal')
        ->assertCalled('Lemonfiber.Reveal')
        ->assertCalled('Lemonfiber.IsProtected');
});

it('reads a protected window out of the answer', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Conceal', ['protected' => true]);

    expect(new Screen()->conceal())->toBeTrue();
});

it('reads an unprotected window out of the answer', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Reveal', ['protected' => false]);

    expect(new Screen()->reveal())->toBeFalse();
});

it('reports unprotected where the bridge answers with an error', function (): void {
    // What a development machine with no device attached actually answers.
    // There is no `protected` key in it, and the window is not protected — so
    // the honest reading is the same as the absent one.
    FakeBridge::enable()->respondTo('Lemonfiber.IsProtected', [
        'status' => 'error',
        'code' => 'NO_DEVICE',
        'message' => 'No device connected.',
    ]);

    expect(new Screen()->isProtected())->toBeFalse();
});

it('reports unprotected where the answer is not JSON at all', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.IsProtected', 'not json');

    expect(new Screen()->isProtected())->toBeFalse();
});

it('reports unprotected where the bridge answers nothing', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.IsProtected', null);

    expect(new Screen()->isProtected())->toBeFalse();
});

it('does not read a string as a protected window', function (): void {
    // The reason the check is `=== true` rather than truthy. `"false"` is a
    // non-empty string, and a loose read of it is the one bug in this file that
    // would put a credential on a screen somebody believed was protected.
    FakeBridge::enable()->respondTo('Lemonfiber.IsProtected', ['protected' => 'false']);

    expect(new Screen()->isProtected())->toBeFalse();
});

it('has a bridge to call at all, once the application has booted', function (): void {
    // The fact `Screen::ask()` rests on, pinned where it will be read.
    //
    // `nativephp_call()` is not autoloaded — `nativephp/mobile`'s service
    // provider `require_once`s it during boot — so before that it does not
    // exist. A `Screen` is only ever obtained from the container, which is after
    // every provider has registered, so it always does by then.
    //
    // That is what makes a `function_exists()` guard unreachable rather than
    // careful, and why there is not one. If this ever fails, put it back: the
    // alternative is a fatal on somebody's phone.
    expect(function_exists('nativephp_call'))->toBeTrue();
});
