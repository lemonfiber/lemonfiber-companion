<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a verdict was written down for the next opening.
 *
 * The quietest outcome in this application, and deliberately so. A device that
 * will not keep a verdict has cost the operator nothing they were promised:
 * the screen that asked is holding the live answer and is showing it, and the
 * only consequence is that the next opening has nothing to open on — which
 * {@see Showing::waiting()} already means and already renders.
 *
 * So there is no remedy on the refused arm and no {@see Obstacle}. Both would
 * be this app asking somebody to go and fix something in order to make a
 * future screen marginally faster, which is not a thing to interrupt anybody
 * about.
 *
 * **It is still an outcome rather than nothing.** `C1` keeps `void` out of a
 * published signature, and the reason applies here even though no screen shows
 * this: a caller that cannot see the answer cannot log it, cannot test it, and
 * cannot later decide the answer matters. {@see Kept} makes the same shape for
 * sessions, where the refusal does reach a screen.
 */
final readonly class Noted
{
    private function __construct(private ?Instant $writtenAt) {}

    /**
     * Written down; the next opening will have it.
     *
     * Carries the moment rather than a flag, which is what keeps `D5` happy
     * and is also the more honest value: the thing that was written is a
     * verdict *and a time*, and the arm saying it was written can say when.
     */
    public static function downAt(Instant $at): self
    {
        return new self($at);
    }

    /**
     * The device would not keep it, and nothing is asked of anybody.
     *
     * No reason is carried. The store's own words are about the store and
     * there is no screen to say them on, so carrying one would be a value
     * nothing reads — which is a value no test can tell from any other.
     */
    public static function notKept(): self
    {
        return new self(null);
    }

    /**
     * @template TDown of object
     * @template TNotKept of object
     *
     * @param  Closure(Instant): TDown  $down
     * @param  Closure(): TNotKept  $notKept
     * @return TDown|TNotKept
     */
    public function either(Closure $down, Closure $notKept): object
    {
        // The arm that carries something is read first, as every fold here
        // does: a fall-through is how a branch becomes the one nobody tested.
        return $this->writtenAt instanceof Instant
            ? $down($this->writtenAt)
            : $notKept();
    }
}
