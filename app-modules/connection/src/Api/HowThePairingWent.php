<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

use function sprintf;

/**
 * What became of a pairing the operator confirmed.
 *
 * A screen's state after the tap, and the reason it is an enum rather than a
 * boolean is the middle case: a pairing that was confirmed and could not be
 * written down has *not* happened, and an operator told "paired" who finds
 * nothing on the next launch has been lied to by an app that had the
 * information at the time.
 *
 * **The two refusals are named rather than merged.** `N1-R10`'s habit applied
 * to storage: a device offering no store at all is a thing the operator cannot
 * fix by trying again, and a store that would not open — locked, full, refusing
 * — is a thing they very often can. One sentence for both is the sentence that
 * is unhelpful for whichever they are actually in.
 *
 * **Mapped from {@see WhyAStackCannotBeRemembered} rather than repeating it.**
 * The kernel's enum is the fact; this is what a surface does about it, which is
 * a different question with the same arity today and no promise of that
 * tomorrow. {@see refused()} converts with a `match` that has no default arm,
 * so a third reason added to the kernel fails here by name rather than falling
 * silently into whichever case was written last.
 */
enum HowThePairingWent: string
{
    /** The operator has not confirmed anything yet. The state a screen opens in. */
    case NotYet = 'not_yet';

    /** The stack is paired and written down where the next launch will find it. */
    case Paired = 'paired';

    /** There is nowhere on this device this app may write a stack. */
    case NoStoreOnThisDevice = 'no_store_on_this_device';

    /** There is a store and it would not open, which is often temporary. */
    case TheStoreWouldNotOpen = 'store_would_not_open';

    /** Whether the stack is paired and written down. */
    public function isPaired(): bool
    {
        return $this === self::Paired;
    }

    /** Whether the operator has confirmed anything yet. */
    public function isNotYet(): bool
    {
        return $this === self::NotYet;
    }

    /**
     * The key for the sentence naming what became of the pairing.
     *
     * **Built from the case rather than listed against it**, which is
     * {@see \Modules\Kernel\Api\Permission::reason()}'s shape and
     * {@see HowTheSignInWent}'s: a `match` naming a key per case spells every
     * stem twice — once as the case's value and once as the string beside it —
     * and two spellings of one name drift.
     *
     * **{@see self::NotYet} has no key here and must not be asked.** What a
     * screen says before anything has happened is what *that screen is for*,
     * and the two pairing roads are for different things — one points a camera,
     * the other takes dictation. An outcome cannot answer for them, so each
     * screen answers for itself and asks this only once there is an outcome.
     * `EveryDerivedKeyResolvesTest` holds the pairs that do exist.
     */
    public function said(): string
    {
        return sprintf('connection.%s', $this->value);
    }

    /**
     * The key for what to do about it.
     *
     * Separate from {@see said()} because `N1-R10` asks for both and they are
     * not the same sentence: what happened is a fact, and what to do about it
     * is advice. `_action` is the suffix every remedy in this catalogue carries.
     */
    public function remedy(): string
    {
        return sprintf('connection.%s_action', $this->value);
    }

    /** What a surface shows for each reason the stack could not be written down. */
    public static function refused(WhyAStackCannotBeRemembered $why): self
    {
        return match ($why) {
            WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage => self::NoStoreOnThisDevice,
            WhyAStackCannotBeRemembered::StoreWouldNotOpen => self::TheStoreWouldNotOpen,
        };
    }
}
