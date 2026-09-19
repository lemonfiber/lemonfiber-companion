<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a household member asked this machine for, and where each one stands.
 *
 * The sibling of {@see WhatTheyAreOwed} over the same reading, and the difference
 * is what is being answered rather than who it is about. That one carries the
 * sentences the core wrote about *asking* — the policy, what the period has left,
 * when it makes room. This carries the things already asked for, each with the
 * state it is in.
 *
 * **Their own, because the core narrowed it.** A session belonging to a member is
 * answered with that member's row, so what arrives here is theirs and nothing had
 * to pick it out. An app that read a household and drew one person's requests off
 * it would be deciding who is looking, and it would be one mistake away from
 * showing somebody another member's — which is the thing a member must never be
 * shown.
 *
 * **A state and never a stage.** What a member reads is whether a thing is waiting
 * on somebody, on its way, here, or refused and why. The pipeline behind that — a
 * queue position, a percentage, which service is fetching — is the machine's
 * business and means nothing to the person waiting for a film.
 *
 * **Told and refused, and empty is neither.** A member who has asked for nothing
 * has an empty list and that is an ordinary answer, said in as many words. A stack
 * that would not say is a different thing, and a surface drawing both as an empty
 * list would tell somebody they have asked for nothing when the truth is that
 * nobody could find out.
 */
final readonly class WhatTheyAsked
{
    private function __construct(private Requested $wanted, private ?Obstacle $why) {}

    /**
     * The stack answered, and this is what they have asked for.
     *
     * An empty list is an ordinary answer rather than a missing one, for the
     * reason {@see WhatTheyAreOwed::told()} gives: a member who has asked for
     * nothing is told so, rather than shown a screen that simply has nothing on
     * it.
     */
    public static function told(Requested $wanted): self
    {
        return new self(wanted: $wanted, why: null);
    }

    /**
     * The stack would not say, and this is what stood in the way.
     *
     * Kept apart from an empty telling because the two draw the same and read as
     * opposites: one says you have asked for nothing, and the other says nobody
     * could find out what you asked for.
     */
    public static function refused(Obstacle $why): self
    {
        return new self(wanted: Requested::none(), why: $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TTold of object
     * @template TRefused of object
     *
     * @param  Closure(Requested): TTold  $told
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        // Read off the refusal, the way {@see WhatTheyAreOwed} does and for its
        // reason: the arm with something to explain is the one the type is written
        // around, and a fall-through is how a branch becomes the one nobody tested.
        return $this->why instanceof Obstacle ? $refused($this->why) : $told($this->wanted);
    }
}
