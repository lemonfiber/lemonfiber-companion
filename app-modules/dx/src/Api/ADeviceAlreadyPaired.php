<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\Stacks;
use Modules\Vault\Api\PlatformStacks;

/**
 * A device that has already been introduced to a machine.
 *
 * The other half of *operate*, and the half without which the first is worth
 * little: {@see AStackThatIsNotThere} makes a stack answer, and every screen
 * that asks one anything sits behind a pairing. A device holding none reaches
 * exactly one frame of this application — the first run — so standing in for
 * the stack alone leaves the rest of it as unreachable as it was.
 *
 * **Two affordances rather than one, on purpose.** Folding them together would
 * make the first run the one thing that could never be looked at, and it is the
 * sequence most worth looking at: it is built a step at a time and
 * a paired device never sees it again. Kept apart, a stand-in
 * stack with no pairing is the first run with something behind it, and both
 * together is the app an operator uses every day.
 *
 * **The pairing is real, and only the keychain is not.** What answers the port
 * is `PlatformStacks` — the shipped adapter, writing the JSON it always writes
 * and applying `Configured::with()`'s re-pairing rule — over
 * {@see TheStoreThisRunKeeps}, which holds it in this process. So pairing
 * another machine while this is on behaves exactly as it does on a real device
 * for as long as the app is open, and leaves nothing behind when it closes.
 * The second half is asked for in as many words.
 *
 * @implements StandsIn<Stacks>
 */
final readonly class ADeviceAlreadyPaired implements StandsIn
{
    public function __construct(private TheStoreThisRunKeeps $store)
    {
        $stacks = new PlatformStacks($this->store);

        // Written through the adapter rather than into the store directly, so
        // the shape the store holds is the shape the adapter reads — a seeded
        // value assembled here would be this class's idea of that shape, which
        // is the fixture-written-by-its-reader problem one layer down.
        //
        // Every machine rather than one. A device holds more than one stack,
        // and `AStandInStack` says why three: a build where only the working
        // one can be reached is a build where the obstacle screens — the ones an
        // operator meets on a bad evening — are never looked at.
        foreach (AStandInStack::cases() as $standIn) {
            $stacks->remember($standIn->asAStack());
        }
    }

    public function insteadOf(): string
    {
        return Stacks::class;
    }

    /**
     * Built per call over a store that is not, which is the whole arrangement.
     *
     * The port promises a fresh adapter and the real binding is not a
     * singleton, so this answers the same way. What persists is the store
     * behind it — as the keychain persists behind the real one — which is what
     * makes a pairing made during the run still there a screen later.
     */
    public function which(): Stacks
    {
        return new PlatformStacks($this->store);
    }
}
