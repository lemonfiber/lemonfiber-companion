<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where the stack stands on being up to date, as the stack reported it.
 *
 * One reading of the `update` envelope: where the services stand against their
 * pins, what taking the update would change, the release that is running and
 * the release history, and what became of the last update. Held together
 * because they arrive in one payload and answer one question.
 *
 * **Whether there is an update comes from the pins, not from the changelog.**
 * The stack moves services onto the versions its own build pins, and says
 * whether any would move in {@see AgainstThePins}. The release history is every
 * release the record holds, up to and including this build: it explains where
 * the pins came from and is never a list of offers.
 */
final readonly class Upkeep
{
    private function __construct(
        private AgainstThePins $pins,
        private Releases $history,
        private Services $changing,
        private Services $cannotBePutBack,
        private HowServicesTookIt $went,
        private ?Release $inUse = null,
    ) {}

    /**
     * A reading where the stack did not say which release is running.
     *
     * Its own constructor rather than a null argument, which is `C2`'s cure and
     * {@see Daemon::thatExited()}'s shape: a screen handed a null would print
     * an empty version where one belongs, and an operator would read that as
     * *it is running nothing*.
     */
    public static function reported(
        AgainstThePins $pins,
        Releases $history,
        Services $changing,
        Services $cannotBePutBack,
        HowServicesTookIt $went,
    ): self {
        return new self($pins, $history, $changing, $cannotBePutBack, $went);
    }

    /** The same reading, where the stack named the release that is running. */
    public static function runningOn(
        AgainstThePins $pins,
        Release $inUse,
        Releases $history,
        Services $changing,
        Services $cannotBePutBack,
        HowServicesTookIt $went,
    ): self {
        return new self($pins, $history, $changing, $cannotBePutBack, $went, $inUse);
    }

    public function againstThePins(): AgainstThePins
    {
        return $this->pins;
    }

    /**
     * Say the release that is running, or say the stack did not name one.
     *
     * Two arms rather than a nullable getter, for the reason
     * {@see Daemon::exit()} gives: a stack that has not looked is not a stack
     * running nothing.
     *
     * The running release is also the one whose build carries the pins, so its
     * notes are what the stack says an update would bring.
     *
     * @template TNamed of object
     * @template TUnstated of object
     *
     * @param  Closure(Release): TNamed  $named
     * @param  Closure(): TUnstated  $unstated
     * @return TNamed|TUnstated
     */
    public function inUse(Closure $named, Closure $unstated): object
    {
        return $this->inUse instanceof Release ? $named($this->inUse) : $unstated();
    }

    /** Every release the stack's record holds, newest first, withdrawn ones included. */
    public function history(): Releases
    {
        return $this->history;
    }

    /**
     * The services taking the update would change.
     *
     * What the confirmation names. Carried on the reading rather than
     * asked for when the operator taps, because a list fetched after the yes is
     * a list of whatever the stack had by then.
     */
    public function changing(): Services
    {
        return $this->changing;
    }

    /**
     * The services taking it would change in a way nothing puts back.
     *
     * A subset of {@see changing()}. Empty where every change can be undone.
     */
    public function cannotBePutBack(): Services
    {
        return $this->cannotBePutBack;
    }

    /** Whether the release that is running has been taken back. */
    public function runningAWithdrawnRelease(): bool
    {
        return $this->inUse instanceof Release && $this->inUse->wasWithdrawn();
    }

    /**
     * Whether there is an update to offer at all.
     *
     * Three conditions. The stack said a service would move; at least one
     * service would, once the changes it refused are left out; and the release
     * that carries the pins has not been withdrawn, because moving onto a
     * withdrawn release's pins is taking that release.
     */
    public function hasSomethingToOffer(): bool
    {
        return $this->pins->hasAnUpdateToTake()
            && ! $this->changing->isEmpty()
            && ! $this->runningAWithdrawnRelease();
    }

    /** What became of each service the last applied update touched. */
    public function howItWent(): HowServicesTookIt
    {
        return $this->went;
    }
}
