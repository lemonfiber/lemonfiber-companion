<?php

declare(strict_types=1);

namespace Modules\Dx\Adapters;

use function array_key_exists;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WasRead;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\Wrote;
use Override;

/**
 * A keychain that lives for one run and touches no device.
 *
 * The same move {@see \Modules\Dx\Api\ClientsThatReachNothing} makes one layer
 * down: the adapters above this are the real ones — `PlatformStacks` writes the
 * same JSON, applies the same re-pairing rule, and refuses for the same reasons
 * — and the only thing replaced is the part that leaves the process. A
 * stand-in written at the `Stacks` port instead would skip the serialising,
 * which is where a stored pairing has actually gone wrong before.
 *
 * **It writes nothing to the device, which is required in as many
 * words.** An operator who pairs a real machine while stand-ins are on gets a
 * pairing that works for the rest of the run and is gone on the next launch —
 * the conservative direction, and the one that means turning this on can never
 * leave anything behind.
 *
 * **Mutable, and it has to be.** A store that cannot be written to is not a
 * store, and the whole reason this exists rather than a fixed answer at the
 * port is that `remember()` has to stick: a screen that reported a pairing and
 * then did not show it would be a lie on the glass, which is worse than not
 * offering the affordance at all. `ModuleBoundariesTest` names it for that
 * reason rather than exempting the module.
 *
 * **It never refuses.** Every read is found or nothing, and every write is
 * done: this store is always reachable, so reporting otherwise would put a
 * screen in front of somebody for a condition that cannot arise here. The
 * screens for a device with no store and for a store that would not open are
 * worth looking at, and a stand-in raising them at random is not how to look at
 * them — that is a second affordance, and one that should say which failure it
 * is standing in for.
 */
final class TheStoreThisRunKeeps implements Keeps
{
    /** @var array<string, string> */
    private array $held = [];

    /**
     * There is somewhere to keep a value, because this is it.
     *
     * The answer a handset with a working keystore gives, which is the one that
     * lets a run reach the screens past pairing. A stand-in answering *no store
     * on this device* would put every one of them behind a refusal.
     */
    #[Override]
    public function canBeAsked(): bool
    {
        return true;
    }

    /**
     * The accessibility argument is accepted and answered back unchanged.
     *
     * It says when the platform may decrypt a value — after first unlock, while
     * the device is unlocked — and there is no platform here to tell. Answering
     * with what was asked for is honest in a way a fixed reply would not be:
     * nothing here locks, so there is no narrower promise to report having
     * fallen back to.
     */
    #[Override]
    public function keep(string $key, string $value, WhenAValueMayBeRead $when): Wrote
    {
        $this->held[$key] = $value;

        return Wrote::done($when);
    }

    #[Override]
    public function read(string $key): WasRead
    {
        return array_key_exists($key, $this->held)
            ? WasRead::found($this->held[$key])
            : WasRead::nothing();
    }

    #[Override]
    public function forget(string $key): Wrote
    {
        unset($this->held[$key]);

        return Wrote::done(WhenAValueMayBeRead::WhileUnlocked);
    }
}
