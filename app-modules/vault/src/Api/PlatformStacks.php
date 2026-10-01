<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\RemovalsUnderWay;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\StacksBeingRemoved;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\ThePairings;
use Modules\Vault\Internal\ThePairingsAsWritten;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

/**
 * The stacks this device is paired with, kept in the platform's own store.
 *
 * **The keychain rather than a file, and that is a decision rather than reuse.**
 * Secure storage is about sessions and does not reach this, so an ordinary file
 * would satisfy every requirement that names one. What it would not satisfy is
 * the one treating a stack's address as private: a file in the app's
 * sandbox is readable by a backup, by a device that has been rooted, and by
 * whatever a restore puts it back onto. The record here is an address, a
 * certificate digest and a name somebody chose for their house. The store that
 * already exists for the session is the conservative place to put it, and this
 * package offers no other.
 *
 * **One key for the whole record**, unlike {@see PlatformKeychain}'s key per
 * stack. They differ because sessions must be separable so that
 * one can be forgotten without touching another, and the configured list is a
 * single value that is read whole on every launch. A key per stack here would
 * mean enumerating a store that offers no enumeration.
 *
 * **A shape number, because everything retained carries one.** The version of
 * the shape a value was written in travels with it, and a shape this build does
 * not recognise is migrated or discarded — never interpreted as though it were
 * current. There is one shape so far, so there is nothing to migrate
 * and discarding is what happens: a record from a newer build of this app, or a
 * record that is not this record at all, reads as *no stacks configured* and
 * the operator lands on the screen for a device with no stacks.
 *
 * That is the conservative direction and it is worth being explicit about the
 * alternative. Reading an unrecognised record optimistically — taking the parts
 * that parse — would produce a stack list assembled from something nobody
 * wrote, which is an app offering to operate a machine it cannot name.
 */
final readonly class PlatformStacks implements RemovalsUnderWay, Stacks
{
    /**
     * What the record holds once every pairing has been forgotten.
     *
     * The store keeps the key and writes an empty list into it, so a device
     * that has been unpaired answers `Found` with something in it. Named rather
     * than compared against inline: `D4` refuses a value checked against a
     * literal, and the reason applies here — this is the encoding's word for
     * empty, and it belongs beside the encoding.
     */
    private const string NOTHING_WRITTEN_DOWN = '[]';

    public function __construct(private Keeps $store) {}

    /**
     * Every stack this device is paired with, as far as it can tell.
     *
     * **A store that could not be asked answers the same as a store holding
     * nothing, and that is a known collapse rather than an oversight.** The two
     * are opposite — one device is unpaired, the other cannot say whether it is
     * — and telling them apart here means `Configured` carrying a refusal to
     * every screen that draws a stack list. That is a change to the port and to
     * around a dozen call sites, so it is not made on the way past; the arm is
     * written out so that the collapse is visible at the point it happens
     * rather than hidden behind a status comparison.
     *
     * {@see holdsAny()} is the one question where the distinction already
     * matters enough to be made, because getting it wrong there unlocks the app.
     *
     * A stack whose removal has begun is left out, whether or not its pairing
     * has been let go of yet: it is in no list from the moment its removal is
     * written down, because a removal is one act to the operator however many
     * steps it takes. The removals are kept in the same record, so leaving them
     * out costs no second read.
     */
    public function configured(): Configured
    {
        return $this->pairings()->listed();
    }

    /**
     * Whether the record exists and holds something, without reading what.
     *
     * The status and the emptiness of the value, and nothing parsed: a shut app
     * asking this has asked whether there is anything behind its lock, and
     * decoding the pairings to answer would be exactly the reading the lock sits
     * in front of.
     *
     * **It does not agree with {@see Configured()} on a store that will not
     * open, and that is deliberate.** The two are asked different questions
     * there: one is *list what this device holds*, which an unreadable store
     * cannot answer and which reports nothing; the other is *is there anything
     * to protect*, which an unreadable store cannot rule out. The contract
     * suite holds them to each other over a working store, which is where the
     * agreement is a property worth having.
     */
    public function holdsAny(): bool
    {
        return $this->store->read(KeptUnder::Stacks->value)->either(
            found: static fn(string $written): WhetherAnythingIsHeld => $written === ''
                || $written === self::NOTHING_WRITTEN_DOWN
                    ? WhetherAnythingIsHeld::itIsNot()
                    : WhetherAnythingIsHeld::itIs(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            // A store that cannot be read is not a store that is empty. The two
            // arrive here as the same absence and they are opposite answers to
            // the question this method is actually asked: `Opening` uses it to
            // decide whether there is anything worth locking, so reading an
            // unreadable store as *nothing* is the lock letting itself off on
            // exactly the device where something is already wrong — the
            // pairings are still in the store, the app simply cannot see them
            // this launch.
            //
            // Unknown is answered as *there may be*, and for both refusals
            // rather than one. Being wrong in that direction costs a prompt in
            // front of somebody who has paired nothing; being wrong the other
            // way costs them an unlocked application.
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    public function remember(Stack $stack): Remembered
    {
        // Folded into what is already there rather than written on its own, so
        // that re-pairing replaces a machine instead of adding a second row —
        // the rule lives in `Configured::with()` and this does not restate it.
        // Pairing a stack again is the operator taking it back, so a removal
        // of it that had not finished is over with it.
        $written = ThePairingsAsWritten::written($this->pairings()->pairing($stack));

        // Every part of the record is a string this build validated on its way
        // into a value type, so there is no input an operator can supply that
        // reaches this branch. It is still answered rather than asserted, and
        // `StoreWouldNotOpen` is the honest word: nothing was written down, and
        // the remedy offered — try again — is the right one for a condition
        // this application cannot describe any better than that.
        if ($written === false) {
            return Remembered::refused(WhyAStackCannotBeRemembered::StoreWouldNotOpen);
        }

        return $this->store->keep(KeptUnder::Stacks->value, $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): Remembered => Remembered::safely(),
            refused: static fn(WhyNothingWasKept $why): Remembered => Remembered::refused(self::meaning($why)),
        );
    }

    public function putInOrder(StackId ...$order): bool
    {
        return $this->kept($this->pairings()->inTheOrderOf(...$order));
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $pairings = $this->pairings();

        if (! $pairings->stacks->knows($stack)) {
            return Forgotten::nothing();
        }

        return $this->kept($pairings->unpairing($stack)) ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->read(KeptUnder::Stacks->value)->either(
            found: static fn(string $written): WhetherAnythingIsHeld => ThePairingsAsWritten::read($written)->stacks->knows($stack)
                ? WhetherAnythingIsHeld::itIs()
                : WhetherAnythingIsHeld::itIsNot(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    /**
     * Write down that removing this stack has begun, in the record of the
     * pairing it removes, so the two are one read and cannot disagree.
     */
    public function begin(StackId $stack): bool
    {
        return $this->kept($this->pairings()->removing($stack));
    }

    public function underWay(): StacksBeingRemoved
    {
        return $this->pairings()->removing;
    }

    public function finished(StackId $stack): bool
    {
        return $this->kept($this->pairings()->removed($stack));
    }

    /** What one of the store's refusals means in the terms this application reasons in. */
    private static function meaning(WhyNothingWasKept $why): WhyAStackCannotBeRemembered
    {
        return match ($why) {
            WhyNothingWasKept::NoStoreOnThisDevice => WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage,
            WhyNothingWasKept::StoreWouldNotOpen => WhyAStackCannotBeRemembered::StoreWouldNotOpen,
        };
    }

    /** The record as the store holds it; one that cannot be read holds nothing. */
    private function pairings(): ThePairings
    {
        return $this->store->read(KeptUnder::Stacks->value)->either(
            found: static fn(string $written): ThePairings => ThePairingsAsWritten::read($written),
            nothing: static fn(): ThePairings => ThePairings::none(),
            refused: static fn(): ThePairings => ThePairings::none(),
        );
    }

    /**
     * Write the record down in place of the one before, and say whether it was.
     *
     * A record with no pairing and no removal under way is let go of rather
     * than written empty: a phone paired with nothing holds nothing, and the
     * lock reads it so.
     */
    private function kept(ThePairings $pairings): bool
    {
        if ($pairings->isEmpty()) {
            return $this->store->forget(KeptUnder::Stacks->value)->either(
                done: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
                refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            )->held;
        }

        $written = ThePairingsAsWritten::written($pairings);

        if ($written === false) {
            return false;
        }

        return $this->store->keep(KeptUnder::Stacks->value, $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
        )->held;
    }
}
