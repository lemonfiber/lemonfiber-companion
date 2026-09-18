<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the app found when it opened, as one of four things it can be.
 *
 * A launch with no network, a launch that cannot reach the stack
 * and a launch where the app is locked are told apart — and two more
 * add the two that are not failures at all: no stack paired yet, and a stack
 * paired and ready. Four answers, and the point of the type is that they cannot
 * be collapsed into "did it work".
 *
 * **Locked is not an `Obstacle`.** That is the distinction this type exists for.
 * The obstacles are things standing between the app and a stack — a network
 * that is down, a machine that is off, a credential refused. Locked is none of
 * those: the app has not tried, and must not. Putting it in `Obstacle` would
 * give it a remedy shaped like the others, and an operator would be told to
 * check their router when what they need to do is look at their phone.
 *
 * **No stack paired is not an obstacle either**, for the same reason one level
 * along: nothing is wrong. That launch reaches a screen offering
 * pairing, and reporting it as a failure to reach would be the app describing
 * its own first run as a fault.
 *
 * Read by saying what happens in every case, the way {@see Reach} is read, so
 * there is no point at which "the launch did not go well" exists as a value on
 * its own — which is the form the four collapse into.
 *
 *     $launch->either(
 *         locked: fn (): Screen => $this->askToUnlock(),
 *         unpaired: fn (): Screen => $this->offerPairing(),
 *         blocked: fn (Obstacle $obstacle): Screen => $this->explain($obstacle),
 *         ready: fn (StackId $stack): Screen => $this->show($stack),
 *     );
 */
final readonly class Launch
{
    private function __construct(
        private bool $locked,
        private ?Obstacle $obstacle,
        private ?StackId $stack,
    ) {}

    /**
     * The app is locked, and nothing has been tried.
     *
     * `N4` defines the lock. What matters here is that this is answered before
     * anything reaches the network: an app that reached a stack and then asked
     * for a passcode has already sent the credential it was holding.
     */
    public static function locked(): self
    {
        return new self(locked: true, obstacle: null, stack: null);
    }

    /** No stack is paired, which is a first run rather than a fault. */
    public static function unpaired(): self
    {
        return new self(locked: false, obstacle: null, stack: null);
    }

    /** Something stood between the app and the stack it is paired with. */
    public static function blockedBy(Obstacle $obstacle): self
    {
        return new self(locked: false, obstacle: $obstacle, stack: null);
    }

    /** A stack is paired and was reached. */
    public static function ready(StackId $stack): self
    {
        return new self(locked: false, obstacle: null, stack: $stack);
    }

    /**
     * Say what happens in each of the four, and get back what you built.
     *
     * Every arm is required. An optional one would be a default, and a default
     * is where two of these quietly become the same answer — which is the whole
     * of what is refused.
     *
     * @template TLocked of object
     * @template TUnpaired of object
     * @template TBlocked of object
     * @template TReady of object
     *
     * @param Closure(): TLocked         $locked
     * @param Closure(): TUnpaired       $unpaired
     * @param Closure(Obstacle): TBlocked $blocked
     * @param Closure(StackId): TReady    $ready
     *
     * @return TLocked|TUnpaired|TBlocked|TReady
     */
    public function either(Closure $locked, Closure $unpaired, Closure $blocked, Closure $ready): object
    {
        // One `return` over four `if`s, and the order is the meaning: a locked
        // app shows the lock whatever else is true, an obstacle outranks a
        // stack that would otherwise be ready, and unpaired is what is left
        // when none of the three hold. Written as a chain of early returns this
        // reads as four independent decisions; written as one expression the
        // precedence is on the page, which is what a reader is checking.
        return match (true) {
            $this->locked => $locked(),
            $this->obstacle instanceof Obstacle => $blocked($this->obstacle),
            $this->stack instanceof StackId => $ready($this->stack),
            default => $unpaired(),
        };
    }
}
