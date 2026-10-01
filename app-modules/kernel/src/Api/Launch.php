<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the app found when it opened, past the lock, as one of three things.
 *
 * A launch that cannot reach the stack is told apart from the two that are not
 * failures at all: no stack paired yet, and a stack paired and ready. Three
 * answers, and the point of the type is that they cannot be collapsed into
 * "did it work".
 *
 * **The lock is not here.** Nothing about a launch is asked until the lock
 * has opened: the screen this is asked on is built only past it.
 *
 * **No stack paired is not an obstacle**: nothing is wrong. That launch reaches a screen offering
 * pairing, and reporting it as a failure to reach would be the app describing
 * its own first run as a fault.
 *
 * Read by saying what happens in every case, the way {@see Reach} is read, so
 * there is no point at which "the launch did not go well" exists as a value on
 * its own — which is the form the three collapse into.
 *
 *     $launch->either(
 *         unpaired: fn (): Screen => $this->offerPairing(),
 *         blocked: fn (Obstacle $obstacle): Screen => $this->explain($obstacle),
 *         ready: fn (StackId $stack): Screen => $this->show($stack),
 *     );
 */
final readonly class Launch
{
    private function __construct(
        private ?Obstacle $obstacle,
        private ?StackId $stack,
    ) {}

    /** No stack is paired, which is a first run rather than a fault. */
    public static function unpaired(): self
    {
        return new self(obstacle: null, stack: null);
    }

    /** Something stood between the app and the stack it is paired with. */
    public static function blockedBy(Obstacle $obstacle): self
    {
        return new self(obstacle: $obstacle, stack: null);
    }

    /** A stack is paired and was reached. */
    public static function ready(StackId $stack): self
    {
        return new self(obstacle: null, stack: $stack);
    }

    /**
     * Say what happens in each of the three, and get back what you built.
     *
     * Every arm is required. An optional one would be a default, and a default
     * is where two of these quietly become the same answer — which is the whole
     * of what is refused.
     *
     * @template TUnpaired of object
     * @template TBlocked of object
     * @template TReady of object
     *
     * @param Closure(): TUnpaired       $unpaired
     * @param Closure(Obstacle): TBlocked $blocked
     * @param Closure(StackId): TReady    $ready
     *
     * @return TUnpaired|TBlocked|TReady
     */
    public function either(Closure $unpaired, Closure $blocked, Closure $ready): object
    {
        // The order is the meaning: an obstacle outranks a stack that would
        // otherwise be ready, and unpaired is what is left when neither holds.
        return match (true) {
            $this->obstacle instanceof Obstacle => $blocked($this->obstacle),
            $this->stack instanceof StackId => $ready($this->stack),
            default => $unpaired(),
        };
    }
}
