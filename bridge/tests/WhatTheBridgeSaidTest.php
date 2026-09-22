<?php

declare(strict_types=1);

use Lemonfiber\Native\WhatTheOperatorSaid;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\WhyNothingWasRead;
use Lemonfiber\Native\WhyNothingWasTold;

// Every word this side reads off the bridge, and what it makes of one it does
// not know.
//
// Five types, one reader each, and two inputs that must not share a path.
// *Nothing said* is a machine with no device behind it — every desktop, every
// CI runner, every test that does not script an answer. *A word this type does
// not know* is a bridge that grew a case since the type was written. They
// arrive differently and they mean different things.
//
// **The one-line version collapses them, and only the mutation floor can see
// it.** Written as `self::tryFrom($said ?? '') ?? self::TheDeviceRefused`, both
// inputs reach the same case by the same route: the empty string is not a case
// of any of these enums, so it falls through exactly where any other
// unrecognised word does, and swapping one for the other changes nothing a
// test can observe. Coverage runs the line and the analyser types it, and both
// are satisfied by a line that cannot be wrong because it cannot be anything.
//
// So the null gets a branch of its own, and these are the three answers each
// reader owes: the word, a word it does not know, and nothing at all.

it('reads the word the bridge said', function (): void {
    // A recognised word that is not the fallback, so that reading and falling
    // back cannot be confused — and the fallback's own word beside it, because
    // arriving on the wire and being assumed are different paths to the same
    // case, and only one of them means the bridge said anything.
    expect(WhatTheOperatorSaid::orNothingSaid('granted'))->toBe(WhatTheOperatorSaid::Granted)
        ->and(WhatTheOperatorSaid::orNothingSaid('not_determined'))->toBe(WhatTheOperatorSaid::NotDetermined)
        ->and(WhenAValueMayBeRead::orTheNarrowest('after_first_unlock'))->toBe(WhenAValueMayBeRead::AfterFirstUnlock)
        ->and(WhenAValueMayBeRead::orTheNarrowest('while_unlocked'))->toBe(WhenAValueMayBeRead::WhileUnlocked)
        ->and(WhyNothingWasKept::orTheStoreWouldNotOpen('no_store_on_this_device'))->toBe(WhyNothingWasKept::NoStoreOnThisDevice)
        ->and(WhyNothingWasKept::orTheStoreWouldNotOpen('store_would_not_open'))->toBe(WhyNothingWasKept::StoreWouldNotOpen)
        ->and(WhyNothingWasRead::orSimplyDismissed('there_is_no_camera'))->toBe(WhyNothingWasRead::ThereIsNoCamera)
        ->and(WhyNothingWasRead::orSimplyDismissed('the_operator_closed_it'))->toBe(WhyNothingWasRead::TheOperatorClosedIt)
        ->and(WhyNothingWasTold::orTheDeviceRefused('no_such_channel'))->toBe(WhyNothingWasTold::NoSuchChannel)
        ->and(WhyNothingWasTold::orTheDeviceRefused('the_device_refused'))->toBe(WhyNothingWasTold::TheDeviceRefused);
});

it('reads a word it does not know as the answer that costs least to be wrong about', function (): void {
    // A bridge that grew a case. Each type has already argued which way to be
    // wrong in its own docblock — the recoverable one, the one that prompts
    // nobody who already refused — and this is where that argument is held to.
    expect(WhatTheOperatorSaid::orNothingSaid('provisional'))->toBe(WhatTheOperatorSaid::NotDetermined)
        ->and(WhenAValueMayBeRead::orTheNarrowest('always'))->toBe(WhenAValueMayBeRead::WhileUnlocked)
        ->and(WhyNothingWasKept::orTheStoreWouldNotOpen('the_key_was_rotated'))->toBe(WhyNothingWasKept::StoreWouldNotOpen)
        ->and(WhyNothingWasRead::orSimplyDismissed('the_lens_is_covered'))->toBe(WhyNothingWasRead::TheOperatorClosedIt)
        ->and(WhyNothingWasTold::orTheDeviceRefused('quiet_hours'))->toBe(WhyNothingWasTold::TheDeviceRefused);
});

it('reads nothing said the same way, by its own route', function (): void {
    // The branch of its own. A machine with no device behind it answers
    // null, and null is not a word: it never reaches `tryFrom`, and no empty
    // string stands in for it.
    //
    // The answer is the same as the one above on purpose. A separate branch
    // buys a readable decision rather than a different result: both inputs are
    // decided, rather than one decided and the other falling in behind it.
    expect(WhatTheOperatorSaid::orNothingSaid(null))->toBe(WhatTheOperatorSaid::NotDetermined)
        ->and(WhenAValueMayBeRead::orTheNarrowest(null))->toBe(WhenAValueMayBeRead::WhileUnlocked)
        ->and(WhyNothingWasKept::orTheStoreWouldNotOpen(null))->toBe(WhyNothingWasKept::StoreWouldNotOpen)
        ->and(WhyNothingWasRead::orSimplyDismissed(null))->toBe(WhyNothingWasRead::TheOperatorClosedIt)
        ->and(WhyNothingWasTold::orTheDeviceRefused(null))->toBe(WhyNothingWasTold::TheDeviceRefused);
});

it('reads the empty string as a word it does not know, rather than as nothing said', function (): void {
    // An empty string is something the bridge said, and it is not a case of
    // any of these types — so it lands where every unrecognised word lands.
    // Worth pinning because it is the value a `?? ''` invents, and a reader
    // meeting these types should not have to wonder whether it is special.
    // It is not.
    //
    // **This test does not notice a `?? ''`**, and saying so is the point: the
    // answer is the same either way, which is exactly why no test holds that
    // line. What refuses it is the mutation floor — with the coalesce in place
    // `EmptyStringToNotEmpty` is an equivalent mutant, `bridge/src` scores
    // below 100 and the shard is red. That is the gate here, and the only
    // one.
    expect(WhatTheOperatorSaid::orNothingSaid(''))->toBe(WhatTheOperatorSaid::NotDetermined)
        ->and(WhenAValueMayBeRead::orTheNarrowest(''))->toBe(WhenAValueMayBeRead::WhileUnlocked)
        ->and(WhyNothingWasKept::orTheStoreWouldNotOpen(''))->toBe(WhyNothingWasKept::StoreWouldNotOpen)
        ->and(WhyNothingWasRead::orSimplyDismissed(''))->toBe(WhyNothingWasRead::TheOperatorClosedIt)
        ->and(WhyNothingWasTold::orTheDeviceRefused(''))->toBe(WhyNothingWasTold::TheDeviceRefused);
});
