<?php

declare(strict_types=1);

namespace Modules\Dx\Adapters;

use function array_key_exists;

use Native\Mobile\SecureStorage as Platform;
use Native\Mobile\SecureStorageAccessibility;
use Native\Mobile\SecureStorageResult;
use Native\Mobile\SecureStorageStatus;
use Override;

/**
 * A keychain that lives for one run and touches no device.
 *
 * The same move {@see \Modules\Dx\Api\ClientsThatReachNothing} makes one layer
 * down: the adapter above this is the real one — `PlatformStacks` writes the
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
 * Extends the platform class rather than implementing a port, because the
 * adapter above takes the concrete type — `Native\Mobile\SecureStorage` is what
 * `PlatformStacks` declares — and inventing a port for it here would be a
 * change to the shipped module made on a stand-in's behalf.
 */
final class TheStoreThisRunKeeps extends Platform
{
    /** @var array<string, string> */
    private array $held = [];

    /**
     * The accessibility argument is accepted and ignored.
     *
     * It says when the platform may decrypt a value — after first unlock, while
     * the device is unlocked — and there is no platform here to tell. Ignoring
     * it is honest; pretending to honour it would be a second thing to keep
     * true about a store with no lock.
     */
    #[Override]
    public function set(string $key, ?string $value, ?SecureStorageAccessibility $accessibility = null): bool
    {
        if ($value === null) {
            return $this->delete($key);
        }

        $this->held[$key] = $value;

        return true;
    }

    #[Override]
    public function get(string $key): ?string
    {
        return $this->read($key)->value;
    }

    /**
     * What is held under one key, in the vocabulary the platform answers in.
     *
     * `NotFound` and never `Unavailable` or `Failed`: this store is always
     * reachable, so reporting otherwise would put a screen in front of somebody
     * for a condition that cannot arise here. The screens for those conditions
     * are worth looking at, and a stand-in that raised them at random is not
     * how to look at them — that is a second affordance, and one that should say
     * which failure it is standing in for.
     */
    #[Override]
    public function read(string $key): SecureStorageResult
    {
        return array_key_exists($key, $this->held)
            ? new SecureStorageResult(SecureStorageStatus::Found, $this->held[$key])
            : new SecureStorageResult(SecureStorageStatus::NotFound);
    }

    #[Override]
    public function delete(string $key): bool
    {
        unset($this->held[$key]);

        return true;
    }
}
