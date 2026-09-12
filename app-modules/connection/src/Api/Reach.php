<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Closure;

/**
 * What came of trying to reach a stack: it worked, or something stood in the way.
 *
 * The type exists so that the three obstacles cannot be collapsed on the way to
 * a screen (`N1-R10`). A caller reads it by saying what happens in both cases,
 * and the blocked arm is handed the `Obstacle` itself rather than a flag — so
 * there is no point at which "it did not work" exists as a value on its own,
 * which is the form the three collapse into.
 *
 *     $reach->either(
 *         made: fn (Session $session): Screen => $this->show($session),
 *         blocked: fn (Obstacle $obstacle): Screen => $this->explain($obstacle),
 *     );
 *
 * **Not `Outcome`, which is the same shape.** `Outcome` carries a `Problem`:
 * the summary, meaning and remedies the server wrote. A reach that failed never
 * got an answer from a server, so there is nothing of the server's to carry —
 * and building a `Problem` here would mean writing those sentences in a module,
 * where they would be English on a Dutch phone (L1). What this carries instead
 * is the case, and the text is looked up against it where a translator exists.
 * The two types stay apart because their payloads are different facts, not
 * because the shape is: collapsing them would mean inventing a `Problem` for
 * something the server never said.
 *
 * There is deliberately no `wasBlocked()` and no `obstacle()`, for the reason
 * `Outcome` gives: a check-then-get pair puts the check where it can be
 * forgotten (C2).
 */
final readonly class Reach
{
    private function __construct(private ?object $reached, private ?Obstacle $obstacle) {}

    /**
     * The stack answered, and this is what came back.
     *
     * An object rather than a value of any type, for the reason `Outcome::done`
     * gives: everything crossing a module boundary here is a named type (D2).
     */
    public static function made(object $reached): self
    {
        return new self($reached, null);
    }

    /** Nothing came back, and this is what stood in the way. */
    public static function blockedBy(Obstacle $obstacle): self
    {
        return new self(null, $obstacle);
    }

    /**
     * Say what happens either way, and get the answer.
     *
     * @template TMade of object
     * @template TBlocked of object
     *
     * @param Closure(object): TMade      $made
     * @param Closure(Obstacle): TBlocked $blocked
     *
     * @return TMade|TBlocked
     */
    public function either(Closure $made, Closure $blocked): object
    {
        // Read off the obstacle rather than off the result, so the failing
        // branch is the one the type is written around. The other way up makes
        // a blocked reach the fall-through — which is how it becomes the case
        // nobody tested.
        if ($this->obstacle instanceof Obstacle) {
            return $blocked($this->obstacle);
        }

        // An object by construction: `made()` requires one, and `blockedBy()`
        // is the branch above.
        /** @var object $reached */
        $reached = $this->reached;

        return $made($reached);
    }
}
