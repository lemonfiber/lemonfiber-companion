<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A value, and whether it was read now or is being remembered.
 *
 * `N1-R9` is the requirement: anything shown that was not read in the current
 * session carries when it was read, and is never presented indistinguishably
 * from a live reading. That rule is broken by omission rather than by
 * disagreement — nobody decides to pass off a cached number as a current one;
 * the timestamp is simply not at hand where the screen is written, and the
 * number renders on its own.
 *
 * So the timestamp is not something a screen has to remember to ask for. There
 * is no `value()`. The only way to reach what is inside is to say what happens
 * in both cases, and the retained arm is handed the value and the moment it was
 * read together — a screen that renders it without the age has to have been
 * given the age and dropped it, which is visible in review in a way a missing
 * call is not.
 *
 *     $reading->either(
 *         live: fn (Findings $findings): Screen => $this->show($findings),
 *         retained: fn (Findings $findings, Instant $at): Screen
 *             => $this->show($findings)->agedSince($at),
 *     );
 *
 * **Both arms carry the value; only one carries a time.** That asymmetry is the
 * type: a live reading has no age to state, and giving it `Clock::now()` to
 * carry would make every reading look like it had one — which is the same
 * collapse from the other direction.
 *
 * The shape is `Outcome`'s and `Reach`'s, and it stays its own class rather
 * than becoming a third use of a generic fold. What makes any of them readable
 * at a call site is the pair of names — `done`/`refused`, `made`/`blocked`,
 * `live`/`retained` — and a shared `left`/`right` would trade that for twenty
 * lines.
 */
final readonly class Reading
{
    private function __construct(private object $value, private ?Instant $readAt) {}

    /**
     * Read in this session, from the stack, just now.
     *
     * An object rather than a value of any type, for the reason `Outcome::done`
     * gives: everything crossing a module boundary here is a named type (D2).
     */
    public static function live(object $value): self
    {
        return new self($value, null);
    }

    /** Held from an earlier session, and this is when it was read. */
    public static function retained(object $value, Instant $readAt): self
    {
        return new self($value, $readAt);
    }

    /**
     * Whether this may stand as the confirmation of an action.
     *
     * `N1-R24` draws the line and it is worth drawing once: a retained reading
     * may open a screen, and it may never be what tells an operator that the
     * thing they just asked for happened. A remembered "running" shown after a
     * restart that failed is the application lying about the one moment the
     * operator was watching.
     */
    public function mayConfirmAnAction(): bool
    {
        return ! $this->readAt instanceof Instant;
    }

    /**
     * Say what happens either way, and get the answer.
     *
     * @template TLive of object
     * @template TRetained of object
     *
     * @param Closure(object): TLive              $live
     * @param Closure(object, Instant): TRetained $retained
     *
     * @return TLive|TRetained
     */
    public function either(Closure $live, Closure $retained): object
    {
        // Read off the timestamp rather than off a flag, and take the retained
        // branch first: that is the case this type exists for, and the other
        // way up makes a remembered value the fall-through — which is how it
        // becomes the one nobody tested.
        if ($this->readAt instanceof Instant) {
            return $retained($this->value, $this->readAt);
        }

        return $live($this->value);
    }
}
