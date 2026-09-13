<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

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
    case TheStoreWouldNotOpen = 'the_store_would_not_open';

    /** Whether the stack is paired and written down. */
    public function isPaired(): bool
    {
        return $this === self::Paired;
    }

    /** Whether this device offers nowhere for a stack to be written down. */
    public function hasNowhereToWriteItDown(): bool
    {
        return $this === self::NoStoreOnThisDevice;
    }

    /** Whether there is a store and it would not open, which often passes. */
    public function couldNotOpenTheStore(): bool
    {
        return $this === self::TheStoreWouldNotOpen;
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
