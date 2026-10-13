<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdAllowAction;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Internal\TheWorkAsked;

/**
 * Choosing what the household may ask for through the SDK: the `household-allow` action, rehearsed, carried out, and followed.
 *
 * Only what the operator named is sent, so what was not named stays as the stack holds it.
 */
final readonly class Allowers implements ChoosingWhatTheHouseholdMayAsk
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($change)->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->action($change));
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheAllowanceAsChanged => WhatTheAllowanceCameTo::in($envelope));
        } catch (HouseholdIsUnreadable $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    private function action(AnAllowanceChange $change): HouseholdAllowAction
    {
        $named = $change->for()->either(
            everybody: static fn(): HouseholdAllowAction => new HouseholdAllowAction(),
            somebody: static fn(SomebodyInTheHousehold $who): HouseholdAllowAction => new HouseholdAllowAction(name: $who->name()),
        );

        $chosen = $change->policy(
            chosen: static fn(HowTheHouseholdAsks $policy): HouseholdAllowAction => new HouseholdAllowAction(name: $named->name, policy: $policy->value),
            asItIs: static fn(): HouseholdAllowAction => $named,
        );

        return $change->limit(
            chosen: static fn(ARequestLimit $limit): HouseholdAllowAction => new HouseholdAllowAction(
                name: $chosen->name,
                policy: $chosen->policy,
                requests: $limit->requests(),
                days: $limit->days(),
            ),
            asItIs: static fn(): HouseholdAllowAction => $chosen,
        );
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
