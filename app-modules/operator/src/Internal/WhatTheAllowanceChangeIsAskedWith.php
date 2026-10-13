<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhoAnAllowanceIsFor;

use function trim;

/**
 * What the operator chose, as a change to what may be asked for, or why it is not one.
 *
 * A limit is two whole numbers or none; a change naming neither a policy nor a limit is said on the screen rather than sent.
 */
final readonly class WhatTheAllowanceChangeIsAskedWith
{
    private function __construct(private AnAllowanceChange|string $answer) {}

    /** The member chosen, blank for the whole household; the policy's word, blank to leave it; the limit's two fields. */
    public static function from(string $who, string $policy, string $requests, string $days): self
    {
        $requests = trim($requests);
        $days = trim($days);

        if (($requests === '') !== ($days === '')) {
            return new self('stacks.allowance_change.limit.both');
        }

        if ($requests !== '' && (! WrittenAsAWholeNumber::is($requests) || ! WrittenAsAWholeNumber::is($days))) {
            return new self('stacks.allowance_change.limit.whole_numbers');
        }

        $for = trim($who) === '' ? WhoAnAllowanceIsFor::everybody() : WhoAnAllowanceIsFor::somebody(SomebodyInTheHousehold::called($who));
        $chosen = HowTheHouseholdAsks::tryFrom($policy);
        $limit = $requests === '' ? null : ARequestLimit::of((int) $requests, (int) $days);

        return new self(match (true) {
            $chosen instanceof HowTheHouseholdAsks && $limit instanceof ARequestLimit => AnAllowanceChange::ofBoth($for, $chosen, $limit),
            $chosen instanceof HowTheHouseholdAsks => AnAllowanceChange::ofPolicy($for, $chosen),
            $limit instanceof ARequestLimit => AnAllowanceChange::ofLimit($for, $limit),
            default => 'stacks.allowance_change.nothing_asked',
        });
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAsked of object
     * @template TNot of object
     *
     * @param Closure(AnAllowanceChange): TAsked $asked
     * @param Closure(string): TNot              $notAskable given the catalogue key saying why
     *
     * @return TAsked|TNot
     */
    public function either(Closure $asked, Closure $notAskable): object
    {
        return $this->answer instanceof AnAllowanceChange ? $asked($this->answer) : $notAskable($this->answer);
    }
}
