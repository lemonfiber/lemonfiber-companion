<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where a request stands, where the stack put it into words at all.
 *
 * The contract leaves `state` absent for a status the request service reported
 * and lemonfiber has no word for, rather than guessing it into the nearest one.
 * That is an ordinary answer from a household with a service this build does
 * not fully speak, and it arrives on one request among many.
 *
 * **It is an answer and not a fault**, which is the whole of why this type
 * exists. A reader that refused it took the household down with it: one request
 * whose status nobody named made every member, and every other request, and the
 * decisions waiting on them, unreadable — a surface whose whole job is showing
 * what is awaiting a decision, showing none of it.
 *
 * **No accessor for the word**, for {@see HowItReaches}'s reason: {@see
 * either()} cannot be entered without saying what an unnamed standing reads as,
 * so no screen can render one as though the stack had named it. {@see
 * wantsADecision()} sits beside the fold rather than inside it because it is a
 * fact about the request rather than a word about it, and it is the line {@see
 * Waiting::wantsADecision()} draws once so two surfaces cannot disagree.
 */
final readonly class HowARequestStands
{
    private function __construct(private ?Waiting $said) {}

    /** The stack named where this stands, in a word this app knows. */
    public static function said(Waiting $said): self
    {
        return new self($said);
    }

    /**
     * The request service reported a status lemonfiber has no word for.
     *
     * Told apart from a word this app does not know, which is a different fault
     * and stays refused: the contract's unions are generated from the same
     * source as this enum, so a standing that arrives spelled out and
     * unrecognised means the two have drifted, and {@see
     * \Tests\Feature\EveryWireValueIsACaseTest} is the gate that says so.
     */
    public static function unnamed(): self
    {
        return new self(null);
    }

    /**
     * Whether this is a request the operator still has to answer.
     *
     * An unnamed standing wants no decision. Not because nothing is waiting —
     * it may well be — but because the app cannot say that it is, and a screen
     * offering to approve something on a guess would be offering to act on a
     * status lemonfiber declined to name.
     */
    public function wantsADecision(): bool
    {
        $said = $this->said;

        return $said instanceof Waiting && $said->wantsADecision();
    }

    /**
     * Whether the operator said no to this.
     *
     * Beside {@see wantsADecision()} and for its reason: a fact about the
     * request rather than a word about it. It decides the shape a request is
     * built in — a declined one carries the sentence it was refused with — so a
     * reader asking it is asking which constructor to call, not what to render.
     *
     * A standing nobody named is not a decline. Taking it for one would demand
     * a refusal reason that was never owed, and refuse the row for want of a
     * sentence nobody wrote.
     */
    public function wasDeclined(): bool
    {
        $said = $this->said;

        return $said === Waiting::Declined;
    }

    /**
     * Say what each reads as, and get back what you built.
     *
     * @template TSaid of object
     * @template TUnnamed of object
     *
     * @param  Closure(Waiting): TSaid  $said  given the standing the stack named
     * @param  Closure(): TUnnamed  $unnamed  given nothing, because there is nothing to give
     * @return TSaid|TUnnamed
     */
    public function either(Closure $said, Closure $unnamed): object
    {
        $standing = $this->said;

        return $standing instanceof Waiting ? $said($standing) : $unnamed();
    }
}
