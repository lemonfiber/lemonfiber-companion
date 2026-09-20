<?php

declare(strict_types=1);

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\Storage;
use Lemonfiber\Native\WasRead;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\Wrote;
use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\Code;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\AStoreOnAHandset;

// The Keeps contract, run against the adapter, the fake and the stand-in.
//
// `G2`'s shape, one layer below the application's own port.
// `Modules\Kernel\Api\SecureStorage` speaks in sessions and refusals a screen
// can show and has its own contract; this one speaks in keys and strings, and
// three implementations answer it — the shipped adapter that reaches a device,
// the fake every vault test stands on, and the stand-in a development build
// runs against.
//
// It matters here more than it reads. Every test of `PlatformKeychain`,
// `PlatformStacks` and `PlatformVerdicts` hands its subject an
// `APlatformStore` and never touches a keychain, so a fake easier to satisfy
// than the platform is a fake that makes all three green about a device that
// does not exist. Two answers had already come apart that way: the fake
// reported every removal done where the adapter refuses one it could not make,
// and the fake could only ever be a store that grants exactly what it was asked
// for — which is one of the two platforms and not the one the answer in the
// port's own signature exists for.
//
// **What is asserted is only what each must promise.** The stand-in is in every
// assertion about keeping, reading and forgetting, and in none about a refusal:
// it says in as many words that it never refuses, because it is always
// reachable and a development build meeting a refusal it cannot clear is a
// screen nobody can get past. A contract that asserted a refusal against it
// would have to be weakened until it asserted nothing, and a weakened contract
// is how a fake drifts.

/** The one key these assertions keep a value under. */
const WHERE_A_VALUE_GOES = 'lemonfiber.session.a1b2c3d4e5f60718';

/** The one value they keep there. */
const WHAT_IS_KEPT_THERE = 'a-value-not-a-secret';

/**
 * The adapter, over a bridge scripted to answer the way a handset would.
 *
 * `FakeBridge` is `nativephp/mobile`'s own seam: it intercepts
 * `nativephp_call()` in-process, so the adapter runs the real call — the
 * function name from the manifest, the JSON out, the JSON back — rather than
 * something built to resemble it.
 *
 * The bridge names are written out as literals, deliberately. `Storage` reaches
 * them through `Call`, so a test spelling them `Call::Keep->value` would agree
 * with a wrong enum and prove nothing; `CallTest` holds the enum against
 * `nativephp.json` separately.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 */
function overAHandsetsStore(AStoreOnAHandset $handset): Storage
{
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Storage.Keep', $handset->keep(...))
        ->respondTo('Lemonfiber.Storage.Read', $handset->read(...))
        ->respondTo('Lemonfiber.Storage.Forget', $handset->forget(...));

    return new Storage();
}

/**
 * Every implementation of the port, each on a device whose store works.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * @return array<string, Keeps>
 */
function everyStoreThatWorks(): array
{
    return [
        'the adapter' => overAHandsetsStore(AStoreOnAHandset::working()),
        'the fake' => APlatformStore::working(),
        'the stand-in' => new TheStoreThisRunKeeps(),
    ];
}

/**
 * The implementations that can be a device the store cannot be asked of.
 *
 * Two of the three. {@see TheStoreThisRunKeeps} is an array in this process and
 * says it never refuses: it is always reachable, so a refusal from it would put
 * a development build in front of a screen for a condition that cannot arise.
 *
 * Matched rather than branched on, so a third refusal added to the wire is a
 * build error here rather than a device this quietly stops being.
 *
 * @return array<string, Keeps>
 */
function everyStoreThatCannotBeAsked(WhyNothingWasKept $why): array
{
    return match ($why) {
        WhyNothingWasKept::NoStoreOnThisDevice => [
            'the adapter' => overAHandsetsStore(AStoreOnAHandset::absent()),
            'the fake' => APlatformStore::absent(),
        ],
        WhyNothingWasKept::StoreWouldNotOpen => [
            'the adapter' => overAHandsetsStore(AStoreOnAHandset::refusing()),
            'the fake' => APlatformStore::refusing(),
        ],
    };
}

/**
 * The implementations that can be a store granting only the wider moment.
 *
 * Android's, and the reason {@see Wrote} carries a moment rather than a bool.
 * The stand-in is not here for the reason it is not in the refusals: it grants
 * whatever it is asked for, which is iOS's answer and the one it says it gives.
 *
 * @return array<string, Keeps>
 */
function everyStoreThatCannotNarrow(): array
{
    return [
        'the adapter' => overAHandsetsStore(AStoreOnAHandset::thatCannotNarrow()),
        'the fake' => APlatformStore::thatCannotNarrow(),
    ];
}

/** What a write came back as, as a word a test can compare. */
function howTheWriteWent(Wrote $wrote): string
{
    return $wrote->either(
        done: static fn(WhenAValueMayBeRead $when): Code => Code::of(sprintf('kept:%s', $when->value)),
        refused: static fn(WhyNothingWasKept $why): Code => Code::of(sprintf('refused:%s', $why->value)),
    )->shown();
}

/** Which of the three arms a read came back as, as a word a test can compare. */
function whatIsHeldThere(WasRead $read): string
{
    return $read->either(
        found: static fn(string $value): Code => Code::of(sprintf('found:%s', $value)),
        nothing: static fn(): Code => Code::of('nothing'),
        refused: static fn(WhyNothingWasKept $why): Code => Code::of(sprintf('refused:%s', $why->value)),
    )->shown();
}

it('keeps a value and hands back the same one', function (): void {
    foreach (everyStoreThatWorks() as $which => $store) {
        $store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, WhenAValueMayBeRead::WhileUnlocked);

        expect(whatIsHeldThere($store->read(WHERE_A_VALUE_GOES)))
            ->toBe(sprintf('found:%s', WHAT_IS_KEPT_THERE), $which);
    }
});

it('holds nothing under a key nothing was kept under', function (): void {
    // Nothing, and never a refusal. The two are the distinction the read type
    // exists for: a launch reading *the store could not be asked* as *there is
    // no such key* offers to pair a machine that is already paired.
    foreach (everyStoreThatWorks() as $which => $store) {
        expect(whatIsHeldThere($store->read('lemonfiber.session.never-kept')))->toBe('nothing', $which);
    }
});

it('holds nothing once a value has been forgotten', function (): void {
    foreach (everyStoreThatWorks() as $which => $store) {
        $store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, WhenAValueMayBeRead::WhileUnlocked);
        $store->forget(WHERE_A_VALUE_GOES);

        expect(whatIsHeldThere($store->read(WHERE_A_VALUE_GOES)))->toBe('nothing', $which);
    }
});

it('keeps what is under one key apart from what is under another', function (): void {
    // One key holding everything is how the second pairing overwrites the
    // first, and the first stack starts answering with somebody else's
    // credential. The adapters above this build the keys; this is the promise
    // they build them against.
    $other = 'lemonfiber.session.b2c3d4e5f6071829';

    foreach (everyStoreThatWorks() as $which => $store) {
        $store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, WhenAValueMayBeRead::WhileUnlocked);
        $store->keep($other, 'another-session-not-a-secret', WhenAValueMayBeRead::WhileUnlocked);
        $store->forget($other);

        expect(whatIsHeldThere($store->read(WHERE_A_VALUE_GOES)))
            ->toBe(sprintf('found:%s', WHAT_IS_KEPT_THERE), $which);
    }
});

it('says there is somewhere to keep a value', function (): void {
    foreach (everyStoreThatWorks() as $which => $store) {
        expect($store->canBeAsked())->toBeTrue($which);
    }
});

it('N4-R6 — tells a device with no store from one whose store would not open', function (): void {
    // The distinction the whole refusal type exists for, and the one place it
    // is read off the platform rather than decided here. The two reach the
    // operator as two screens with two remedies: one says give up on this
    // phone and the other says try again.
    foreach (WhyNothingWasKept::cases() as $why) {
        foreach (everyStoreThatCannotBeAsked($why) as $which => $store) {
            expect(howTheWriteWent($store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, WhenAValueMayBeRead::WhileUnlocked)))
                ->toBe(sprintf('refused:%s', $why->value), sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('reads a store it cannot ask as a refusal rather than as holding nothing', function (): void {
    foreach (WhyNothingWasKept::cases() as $why) {
        foreach (everyStoreThatCannotBeAsked($why) as $which => $store) {
            expect(whatIsHeldThere($store->read(WHERE_A_VALUE_GOES)))
                ->toBe(sprintf('refused:%s', $why->value), sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('refuses to forget where the store itself cannot be asked', function (): void {
    // Nothing was taken out, because there was nothing there to ask. Answering
    // otherwise makes *getting rid of a session always works* a promise this
    // layer is keeping, and it is not: it is the caller's, and
    // `PlatformKeychain` keeps it by not reading this answer at all. A store
    // reporting a removal it did not make takes that decision away from the
    // one place it is written down.
    foreach (WhyNothingWasKept::cases() as $why) {
        foreach (everyStoreThatCannotBeAsked($why) as $which => $store) {
            expect(howTheWriteWent($store->forget(WHERE_A_VALUE_GOES)))
                ->toBe(sprintf('refused:%s', $why->value), sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('N4-R6 — a store that will not open is not a device that has none', function (): void {
    // Different remedies, and this is the answer that chooses between them
    // before a session exists. Telling an operator their phone cannot keep a
    // session is the advice that makes them give up on a phone that works.
    foreach (everyStoreThatCannotBeAsked(WhyNothingWasKept::NoStoreOnThisDevice) as $which => $store) {
        expect($store->canBeAsked())->toBeFalse($which);
    }

    foreach (everyStoreThatCannotBeAsked(WhyNothingWasKept::StoreWouldNotOpen) as $which => $store) {
        expect($store->canBeAsked())->toBeTrue($which);
    }
});

it('answers with the moment the store grants, not the one it was asked for', function (): void {
    // The clause the port states in as many words, and the one an
    // implementation can satisfy by accident: a store that handed the request
    // back would agree with every caller that assumed it would. Android's is
    // the store that does not — its encrypted store is readable whenever the
    // application can run — and a caller told it got the narrower is holding a
    // session readable on a locked phone while believing it is not.
    foreach (everyStoreThatCannotNarrow() as $which => $store) {
        expect(howTheWriteWent($store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, WhenAValueMayBeRead::WhileUnlocked)))
            ->toBe(sprintf('kept:%s', WhenAValueMayBeRead::AfterFirstUnlock->value), $which);
    }
});

it('answers with the moment asked for where the store files a value under it', function (): void {
    // iOS, where accessibility is an attribute of the item. The other half of
    // the clause above: the answer is the store's either way, and it is the
    // request here because the store honoured the request.
    foreach ([WhenAValueMayBeRead::WhileUnlocked, WhenAValueMayBeRead::AfterFirstUnlock] as $asked) {
        foreach (everyStoreThatWorks() as $which => $store) {
            expect(howTheWriteWent($store->keep(WHERE_A_VALUE_GOES, WHAT_IS_KEPT_THERE, $asked)))
                ->toBe(sprintf('kept:%s', $asked->value), sprintf('%s, %s', $which, $asked->value));
        }
    }
});
