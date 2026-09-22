<?php

declare(strict_types=1);

use Lemonfiber\Native\Storage;
use Lemonfiber\Native\WasRead;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\Wrote;
use Native\Mobile\Testing\FakeBridge;

// The storage capability's PHP face, driven through the real bridge call.
//
// `FakeBridge` intercepts `nativephp_call()` in-process, so every assertion here
// goes through the function name, the JSON out and the decoding of the answer
// rather than through something built to resemble it.
//
// The bridge names are written out as literals, deliberately. `Storage` reaches
// them through `Call`, so a test spelling them `Call::Keep->value` would agree
// with a wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json` separately.

beforeEach(function (): void {
    FakeBridge::disable();
});

/**
 * Why a write was refused, or nothing because it was not.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of the same name are a fatal the moment both load (`G10`).
 */
function whyTheStoreRefused(Wrote $wrote): ?WhyNothingWasKept
{
    $answered = $wrote->either(
        done: static fn(): ArrayObject => new ArrayObject([null]),
        refused: static fn(WhyNothingWasKept $why): ArrayObject => new ArrayObject([$why]),
    );

    $why = $answered[0];

    return $why instanceof WhyNothingWasKept ? $why : null;
}

/** When the store said the value may be read again, or nothing where it refused. */
function whenItMayBeReadAgain(Wrote $wrote): ?WhenAValueMayBeRead
{
    $answered = $wrote->either(
        done: static fn(WhenAValueMayBeRead $when): ArrayObject => new ArrayObject([$when]),
        refused: static fn(): ArrayObject => new ArrayObject([null]),
    );

    $when = $answered[0];

    return $when instanceof WhenAValueMayBeRead ? $when : null;
}

/** Which of the three a read came back as, as a word a test can compare. */
function whatTheStoreAnswered(WasRead $read): string
{
    $answered = $read->either(
        found: static fn(string $value): ArrayObject => new ArrayObject([sprintf('found:%s', $value)]),
        nothing: static fn(): ArrayObject => new ArrayObject(['nothing']),
        refused: static fn(WhyNothingWasKept $why): ArrayObject
            => new ArrayObject([sprintf('refused:%s', $why->value)]),
    );

    $said = $answered[0];

    return is_string($said) ? $said : '';
}

it('keeps a value, and says when it may be read again', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Storage.Keep', [
        'outcome' => 'kept',
        'readable' => 'after_first_unlock',
    ]);

    $wrote = new Storage()->keep('lemonfiber.session.one', 'a token', WhenAValueMayBeRead::WhileUnlocked);

    $bridge->assertCalled(
        'Lemonfiber.Storage.Keep',
        static fn(array $sent): bool => $sent['key'] === 'lemonfiber.session.one'
            && $sent['value'] === 'a token'
            && $sent['readable'] === 'while_unlocked',
    );

    // Asked for the narrower and told it got the wider, which is what Android
    // actually gives. A facade echoing the request back would be reporting a
    // promise nobody kept.
    expect(whenItMayBeReadAgain($wrote))->toBe(WhenAValueMayBeRead::AfterFirstUnlock);
    expect(whyTheStoreRefused($wrote))->toBeNull();
});

it('tells a device with no store from one whose store would not open', function (): void {
    foreach ([
        'no_store_on_this_device' => WhyNothingWasKept::NoStoreOnThisDevice,
        'store_would_not_open' => WhyNothingWasKept::StoreWouldNotOpen,
    ] as $word => $why) {
        FakeBridge::disable();
        FakeBridge::enable()->respondTo('Lemonfiber.Storage.Keep', [
            'outcome' => 'refused',
            'because' => $word,
        ]);

        expect(whyTheStoreRefused(new Storage()->keep('k', 'v', WhenAValueMayBeRead::WhileUnlocked)))
            ->toBe($why, $word);
    }
});

it('reads a key that is there', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', [
        'outcome' => 'found',
        'value' => 'a token',
    ]);

    expect(whatTheStoreAnswered(new Storage()->read('k')))->toBe('found:a token');
});

it('tells a key that is not there from a store that could not be asked', function (): void {
    // The distinction the whole type exists for. A launch reading the second as
    // the first offers to pair a machine that is already paired.
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing']);

    expect(whatTheStoreAnswered(new Storage()->read('k')))->toBe('nothing');

    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', [
        'outcome' => 'refused',
        'because' => 'no_store_on_this_device',
    ]);

    expect(whatTheStoreAnswered(new Storage()->read('k')))->toBe('refused:no_store_on_this_device');
});

it('reads a found answer with no value as having found nothing to read', function (): void {
    // The answer a half-written native half would give. An empty string is not
    // a session, and handing one on would put a caller into resuming with
    // nothing to resume.
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'found']);

    expect(whatTheStoreAnswered(new Storage()->read('k')))->toBe('found:');
});

it('forgets a key, and forgets one that was never there', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Storage.Forget', ['outcome' => 'forgotten']);

    expect(whyTheStoreRefused(new Storage()->forget('k')))->toBeNull();

    $bridge->assertCalled('Lemonfiber.Storage.Forget', static fn(array $sent): bool => $sent['key'] === 'k');
});

it('reads no answer at all as a store that would not open', function (): void {
    // Every machine that is not a handset, and the recoverable of the two
    // refusals: it tells an operator to try again, where the other tells them to
    // give up on the phone.
    FakeBridge::enable();

    $storage = new Storage();

    expect(whyTheStoreRefused($storage->keep('k', 'v', WhenAValueMayBeRead::WhileUnlocked)))
        ->toBe(WhyNothingWasKept::StoreWouldNotOpen);
    expect(whatTheStoreAnswered($storage->read('k')))->toBe('refused:store_would_not_open');
    expect(whyTheStoreRefused($storage->forget('k')))->toBe(WhyNothingWasKept::StoreWouldNotOpen);
});

it('reads a moment it does not recognise as the narrowest one', function (): void {
    // Widening on confusion is how a session becomes readable on a locked phone.
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Keep', [
        'outcome' => 'kept',
        'readable' => 'whenever_you_like',
    ]);

    expect(whenItMayBeReadAgain(new Storage()->keep('k', 'v', WhenAValueMayBeRead::AfterFirstUnlock)))
        ->toBe(WhenAValueMayBeRead::WhileUnlocked);
});

it('asks whether there is a store by reading a key nothing is kept under', function (): void {
    // A store that is there answers *nothing under that key*. Asked rather than
    // inferred from a write that failed, because the caller has no value in
    // hand yet — the question is put before a session exists.
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing']);

    expect(new Storage()->canBeAsked())->toBeTrue();

    $bridge->assertCalled(
        'Lemonfiber.Storage.Read',
        static fn(array $sent): bool => $sent['key'] === 'lemonfiber.probe',
    );
});

it('reads a probe that found something as a store that is there', function (): void {
    // Nothing is ever kept under that key, so this is the answer of a store
    // holding something somebody else wrote. It is still a store.
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'found', 'value' => 'whatever']);

    expect(new Storage()->canBeAsked())->toBeTrue();
});

it('says there is a store where one is there and will not open', function (): void {
    // What is wrong there is a condition trying again can clear, and telling an
    // operator their phone cannot keep a session is the advice that makes them
    // give up on a phone that works.
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', [
        'outcome' => 'refused',
        'because' => 'store_would_not_open',
    ]);

    expect(new Storage()->canBeAsked())->toBeTrue();
});

it('says there is no store where the device has none', function (): void {
    // The one answer that sends an operator somewhere else, and the reason the
    // two refusals are kept apart on the wire at all.
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Storage.Read', [
        'outcome' => 'refused',
        'because' => 'no_store_on_this_device',
    ]);

    expect(new Storage()->canBeAsked())->toBeFalse();
});

it('says there is no store where nothing answers at all', function (): void {
    // Every machine that is not a handset. A laptop running the suite has
    // nowhere to keep a session, and answering otherwise would make a test
    // about resuming one pass on a machine that cannot.
    FakeBridge::disable();
    FakeBridge::enable();

    expect(new Storage()->canBeAsked())->toBeFalse();
});

it('does not send a payload it could not encode, and says so as nothing said', function (): void {
    // `"\xB1"` is a continuation byte with nothing in front of it, which is
    // not valid UTF-8 and which `json_encode` refuses by answering false.
    //
    // A `(string)` cast turns that false into `''`, and the call then reaches
    // the device carrying no parameters — a write with no key and no value, which the store
    // would answer for as though something had been kept.
    //
    // Nothing is sent instead, and the answer is the same one every other way
    // of not reaching the bridge gives. The second assertion is the one that
    // matters: the bridge was not called at all.
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Storage.Keep', ['outcome' => 'kept']);

    expect(whyTheStoreRefused(new Storage()->keep("\xB1", 'v', WhenAValueMayBeRead::WhileUnlocked)))
        ->toBe(WhyNothingWasKept::StoreWouldNotOpen)
        ->and($bridge->calls)->toBe([]);
});
