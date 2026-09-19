<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of offering a credential: a session, or the reason there is none.
 *
 * The exchange trades a credential once for a session, and this
 * is what the trade answers with. `C1`'s shape, and this is one of the places
 * it earns itself twice over: a stack that has stopped listening, one that
 * refused the password and one that could not be reached are three ordinary
 * states of the world, and a method returning a `Session` could report them
 * only by throwing.
 *
 *     $admitted->either(
 *         opened: fn (Session $session, Instant $until, Whose $whose): Screen
 *             => $this->carryOn($session, $whose),
 *         refused: fn (Obstacle $why): Screen => $this->explain($why),
 *     );
 *
 * **The refusal is an {@see Obstacle} rather than a type of its own.** The obstacle
 * already owns the vocabulary for things an operator meets on the way to a
 * stack, and a credential being refused is already one of its cases. A second
 * enum would mean two lists of the same kind of fact, and a screen deciding
 * which of the two it was looking at.
 *
 * **`until` travels beside the session rather than inside it**, in
 * {@see Opening}, which carries the reasoning.
 */
final readonly class Admitted
{
    /**
     * One field rather than three, and a union rather than nulls.
     *
     * Three nullable fields would put a `?? throw` in {@see either()} for a
     * state the two named constructors cannot produce — unreachable code, which
     * reads as caution and is a line no test can defend. A union says the same
     * thing and says it to the type system: this is one or the other.
     */
    private function __construct(private Opening|Obstacle $outcome) {}

    /** The credential was traded, and this is what came back. */
    public static function opening(Session $session, Instant $until, Whose $whose): self
    {
        return new self(new Opening($session, $until, $whose));
    }

    /** It was not, and this is what the operator met instead. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `session()` beside a `wasOpened()`, for the reason
     * {@see Outcome} gives and for a sharper one here: a check-then-get pair
     * around a session is a pair somebody forgets, and what they are left
     * holding is a null where a credential should be.
     *
     * @template TOpened of object
     * @template TRefused of object
     *
     * @param Closure(Session, Instant, Whose): TOpened $opened
     * @param Closure(Obstacle): TRefused        $refused
     *
     * @return TOpened|TRefused
     */
    public function either(Closure $opened, Closure $refused): object
    {
        return $this->outcome instanceof Obstacle
            ? $refused($this->outcome)
            : $opened($this->outcome->session, $this->outcome->until, $this->outcome->whose);
    }
}
