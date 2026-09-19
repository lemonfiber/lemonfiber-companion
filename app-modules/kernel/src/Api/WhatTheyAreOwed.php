<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a household member is owed at the moment of asking.
 *
 * The core writes these sentences and this carries them. They say what happens
 * to what the member asks for, what their period has left and when it makes
 * room, what is still waiting on an answer, and what was refused and why —
 * written *to* them rather than about them, so they can be handed over as they
 * stand.
 *
 * **Carried rather than composed, and that is the whole of the type.** The same
 * facts are on the wire in parts — a policy, a standing, two counts, an instant
 * — and a surface assembling its own sentence from them would be a second voice
 * able to disagree with the core's. It would also be a permission model: a
 * screen that decides what *within a limit* means to a person has decided
 * something the core is supposed to decide.
 *
 * **Nothing here is the operator's.** These are the member's own sentences, so
 * this type holds no fault, no code, no remedy and no other member — which is
 * what lets a member surface name it at all.
 *
 * **Told and refused, and empty is neither.** A member the request service
 * holds no account for has nothing waiting and no standing to report, and that
 * is *told, with nothing to say* — a different answer from a stack declining to
 * say anything, which is what a surface must not draw as an empty list.
 */
final readonly class WhatTheyAreOwed
{
    private function __construct(private Sentences $said, private ?Obstacle $why) {}

    /**
     * The core said this, in the words the member reads it in.
     *
     * An empty list is an ordinary answer rather than a missing one: there is
     * nothing to tell them, which a screen says in as many words rather than by
     * showing nothing at all.
     */
    public static function told(Sentences $said): self
    {
        return new self(said: $said, why: null);
    }

    /**
     * The stack would not say, and this is what stood in the way.
     *
     * Kept apart from an empty telling because the two look identical on a
     * screen that draws a list and opposite to the person reading it: one says
     * there is nothing to report, and the other says this was not yours to ask.
     */
    public static function refused(Obstacle $why): self
    {
        return new self(said: Sentences::none(), why: $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TTold of object
     * @template TRefused of object
     *
     * @param  Closure(Sentences): TTold  $told
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        // Read off the refusal, the way `Handed` and `Shown` do: the arm with
        // something to explain is the one the type is written around, and a
        // fall-through is how a branch becomes the one nobody tested.
        return $this->why instanceof Obstacle ? $refused($this->why) : $told($this->said);
    }
}
