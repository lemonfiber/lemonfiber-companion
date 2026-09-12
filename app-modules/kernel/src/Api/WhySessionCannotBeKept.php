<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why a session could not be kept, as the thing an operator would do about it.
 *
 * `N4-R6` requires the app to say why, and these are the two answers a device
 * can actually give. The platform reports `Unavailable` — there is nowhere to
 * put it — or `Failed` — there is somewhere and it did not work. Every finer
 * distinction is a guess.
 *
 * **A third case was written here and removed**, and it is worth saying why: "no
 * screen lock is set" is a real situation with a clear remedy, and the platform
 * does not report it. A case nothing can produce is a branch no adapter reaches,
 * no test kills, and every `match` has to answer anyway — and the first screen
 * to render it would show an operator a remedy for a state the app cannot
 * detect. If the platform grows a way to tell them apart, the case comes back
 * with the adapter arm that fills it.
 *
 * **A `Problem` is not the right carrier**, for the reason `Reach` gives about
 * `Outcome`: a `Problem` carries the summary, meaning and remedies the *server*
 * wrote, and no server was involved in a keychain refusing to open. This carries
 * the case, and the words are looked up against it where a translator exists
 * (`L1`).
 */
enum WhySessionCannotBeKept: string
{
    /**
     * There is nowhere on this device a session may go.
     *
     * `N4-R5` names the alternatives — preferences, an app-readable file, an
     * unencrypted backup — in order to forbid them, so there is no fallback to
     * reach for. The app keeps nothing and says so.
     */
    case DeviceHasNoSecureStorage = 'no_secure_storage';

    /**
     * The store is there and would not open.
     *
     * The one of the two where trying again is sensible. Reported as itself
     * rather than folded into the other, which would tell an operator their
     * device cannot do something it can.
     */
    case StoreWouldNotOpen = 'store_would_not_open';

    /** Whether asking again might get a different answer. */
    public function mayBeWorthRetrying(): bool
    {
        return match ($this) {
            self::StoreWouldNotOpen => true,
            self::DeviceHasNoSecureStorage => false,
        };
    }
}
