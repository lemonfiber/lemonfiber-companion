<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How a capability came to be filled — and, where it is contested, the fact
 * that it has not been.
 *
 * **The prohibition on settling a contest is why this is a union and not a word plus some fields.**
 * The core refuses to settle a contest on the operator's behalf; it says so by
 * sending `contested` with the claimants beside it, and the only correct thing
 * an app can do is show the question. Folded into a string and three nullables,
 * every call site would have to remember which word carries which field — and
 * the one that forgets renders a contest as *filled by whichever came first*,
 * which is this app settling something the core declined to settle.
 *
 * There is no accessor and no `isContested()`. {@see whichever()} cannot be
 * entered without saying what happens in all five cases, so a screen cannot
 * quietly draw nothing for the one arm that matters most. That is the argument
 * {@see WhereASettingCameFrom} makes, and it applies harder here: the arm a
 * careless reader would skip is not a rare edge, it is the one the surface was
 * built for.
 *
 * **`chosen` holds who, and that is the whole of it.** A settlement the stack reached and
 * one the operator made are different facts, and the arm hands out
 * {@see WhoSettledIt} rather than leaving a reader to assume. Its reason is
 * held as written where there is one, and as nothing where there is not: the
 * core marks that field optional, so absence is ordinary rather than a fault,
 * and a blank is normalised to absence rather than shown. *Chosen, because: ▒*
 * asserts a reason and then withholds it, which reads worse than not claiming
 * one — the argument {@see WhereASettingCameFrom::plugin()} makes about a name.
 *
 * **`unfilled` is a state, not a fault.** A stack with a capability nothing
 * claims is not broken, and an arm that made it look like one would be this app
 * being wrong about the product.
 */
final readonly class WhatSettledIt
{
    private function __construct(
        private HowItSettled $arm,
        private Services $services,
        private ?WhoSettledIt $whose,
        private WhyItWasChosen $why,
    ) {}

    /** One service claimed it and nothing else did. */
    public static function outright(): self
    {
        return new self(arm: HowItSettled::Outright, services: Services::none(), whose: null, why: WhyItWasChosen::unstated());
    }

    /** Several services each fill their own, so there was nothing to choose between. */
    public static function each(): self
    {
        return new self(arm: HowItSettled::Each, services: Services::none(), whose: null, why: WhyItWasChosen::unstated());
    }

    /**
     * More than one service claims it, and nobody has decided — these are the
     * claimants.
     *
     * The claimants are carried as they arrived rather than ordered, for
     * {@see \Modules\Health\Api\Queries\WorstFirst}'s reason in reverse: there
     * is no grading here to sort by, so any order this imposed would be an
     * opinion about which service should win. That is the opinion this surface
     * may not hold, arriving through the back door of a sort.
     */
    public static function contested(Services $claimants): self
    {
        return new self(arm: HowItSettled::Contested, services: $claimants, whose: null, why: WhyItWasChosen::unstated());
    }

    /**
     * It was contested and somebody settled it: who, what they settled it over,
     * and why if they said.
     *
     * The reason is required and is never `null`: where nobody gave one, a
     * caller says {@see WhyItWasChosen::unstated()} and means it. A nullable
     * parameter here would make *no reason* and *a reason* the same call, told
     * apart by a check the caller cannot see.
     */
    public static function chosen(Services $over, WhoSettledIt $whose, WhyItWasChosen $why): self
    {
        return new self(arm: HowItSettled::Chosen, services: $over, whose: $whose, why: $why);
    }

    /** Nothing claims it. A state, and one an operator may want to act on. */
    public static function unfilled(): self
    {
        return new self(arm: HowItSettled::Unfilled, services: Services::none(), whose: null, why: WhyItWasChosen::unstated());
    }

    /**
     * Say what happens in all five cases, and get back what you built.
     *
     * @template TOutright of object
     * @template TEach of object
     * @template TContested of object
     * @template TChosen of object
     * @template TUnfilled of object
     *
     * @param  Closure(): TOutright  $outright
     * @param  Closure(): TEach  $each
     * @param  Closure(Services): TContested  $contested  given the claimants
     * @param  Closure(Services, WhoSettledIt, WhyItWasChosen): TChosen  $chosen  given what it was settled over, who settled it, and why — stated or not
     * @param  Closure(): TUnfilled  $unfilled
     * @return TOutright|TEach|TContested|TChosen|TUnfilled
     */
    public function whichever(
        Closure $outright,
        Closure $each,
        Closure $contested,
        Closure $chosen,
        Closure $unfilled,
    ): object {
        $whose = $this->whose;

        // Chosen is told apart by its payload rather than by its word, so the
        // one arm that holds who is the one arm that can hand it out — nothing
        // here can pass a reader a `null` where a `WhoSettledIt` belongs, and
        // that is what keeps a stack's choice from being read as somebody's. Which
        // of the other four it is turns on
        // the word alone, which is a different question and so a second method.
        return $whose instanceof WhoSettledIt
            ? $chosen($this->services, $whose, $this->why)
            : $this->nobodyChose($outright, $each, $contested, $unfilled);
    }

    /**
     * The four arms where nobody settled anything, told apart by the word.
     *
     * @template TOutright of object
     * @template TEach of object
     * @template TContested of object
     * @template TUnfilled of object
     *
     * @param  Closure(): TOutright  $outright
     * @param  Closure(): TEach  $each
     * @param  Closure(Services): TContested  $contested
     * @param  Closure(): TUnfilled  $unfilled
     * @return TOutright|TEach|TContested|TUnfilled
     */
    private function nobodyChose(
        Closure $outright,
        Closure $each,
        Closure $contested,
        Closure $unfilled,
    ): object {
        if ($this->arm === HowItSettled::Contested) {
            return $contested($this->services);
        }

        if ($this->arm === HowItSettled::Each) {
            return $each();
        }

        return $this->arm === HowItSettled::Unfilled ? $unfilled() : $outright();
    }
}
