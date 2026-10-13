<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the operator asks the household, or one member, to be allowed: a policy, a request limit, or both.
 *
 * Whatever is not named stays as it is, so a change carries at least one of the two.
 */
final readonly class AnAllowanceChange
{
    private function __construct(
        private WhoAnAllowanceIsFor $for,
        private ?HowTheHouseholdAsks $policy,
        private ?ARequestLimit $limit,
    ) {}

    /** A new policy, the limit left as it is. */
    public static function ofPolicy(WhoAnAllowanceIsFor $for, HowTheHouseholdAsks $policy): self
    {
        return new self($for, $policy, null);
    }

    /** A new limit, the policy left as it is. */
    public static function ofLimit(WhoAnAllowanceIsFor $for, ARequestLimit $limit): self
    {
        return new self($for, null, $limit);
    }

    /** A new policy and a new limit. */
    public static function ofBoth(WhoAnAllowanceIsFor $for, HowTheHouseholdAsks $policy, ARequestLimit $limit): self
    {
        return new self($for, $policy, $limit);
    }

    /** Whom the change is about. */
    public function for(): WhoAnAllowanceIsFor
    {
        return $this->for;
    }

    /**
     * The policy asked for, or that it stays as it is.
     *
     * @template TChosen of object
     * @template TAsItIs of object
     *
     * @param Closure(HowTheHouseholdAsks): TChosen $chosen
     * @param Closure(): TAsItIs                    $asItIs
     *
     * @return TChosen|TAsItIs
     */
    public function policy(Closure $chosen, Closure $asItIs): object
    {
        return $this->policy instanceof HowTheHouseholdAsks ? $chosen($this->policy) : $asItIs();
    }

    /**
     * The limit asked for, or that it stays as it is.
     *
     * @template TChosen of object
     * @template TAsItIs of object
     *
     * @param Closure(ARequestLimit): TChosen $chosen
     * @param Closure(): TAsItIs              $asItIs
     *
     * @return TChosen|TAsItIs
     */
    public function limit(Closure $chosen, Closure $asItIs): object
    {
        return $this->limit instanceof ARequestLimit ? $chosen($this->limit) : $asItIs();
    }
}
