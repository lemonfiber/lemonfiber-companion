<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a command answers with: it happened, or it was refused.
 *
 * The one type a refusal crosses a module boundary as (C1). Every `Api`
 * command returns one, and the only way to read it is to say what happens in
 * both cases — which is the entire point. A caller that wanted to ignore the
 * refusal would have to write a branch that does nothing, and a branch that
 * does nothing is visible in review where a forgotten `try` is not.
 *
 *     $outcome->either(
 *         done: fn (Repaired $repaired): Screen => $this->show($repaired),
 *         refused: fn (Refusal $refusal): Screen => $this->explain($refusal),
 *     );
 *
 * There is deliberately no `wasRefused()` and no `refusal()`. A pair like that
 * is the check-then-get shape C2 exists to remove: the check is the thing that
 * gets forgotten, and a getter that throws when the other branch was taken
 * just moves the forgetting somewhere the compiler still cannot see it.
 *
 * `either` answers with an object because both arms do. The two arms usually
 * build the same kind of thing — a view model, a screen state — and saying so
 * in the signature is what stops one of them quietly returning null.
 */
final readonly class Outcome
{
    private function __construct(private ?object $result, private ?Refusal $refusal) {}

    /**
     * It happened, and this is what came back.
     *
     * An object rather than a value of any type: everything that crosses a
     * module boundary here is already a named type (D2), so `object` costs
     * nothing and `mixed` would be refused (D3).
     */
    public static function done(object $result): self
    {
        return new self($result, null);
    }

    /** It did not happen, and this is what the operator is told. */
    public static function refused(Refusal $refusal): self
    {
        return new self(null, $refusal);
    }

    /**
     * Say what happens either way, and get the answer.
     *
     * @template TDone of object
     * @template TRefused of object
     *
     * @param Closure(object): TDone     $done
     * @param Closure(Refusal): TRefused $refused
     *
     * @return TDone|TRefused
     */
    public function either(Closure $done, Closure $refused): object
    {
        // The two nullable properties are private and the two constructors are
        // the only way in, so exactly one of them is set. The check is on the
        // refusal rather than the result because that is the branch this type
        // exists for: reading it the other way round would make a refusal the
        // fall-through case, which is how it comes to be the one nobody tested.
        if ($this->refusal instanceof Refusal) {
            return $refused($this->refusal);
        }

        // `$this->result` is an object by construction: `done()` requires one
        // and `refused()` is the branch above.
        /** @var object $result */
        $result = $this->result;

        return $done($result);
    }
}
