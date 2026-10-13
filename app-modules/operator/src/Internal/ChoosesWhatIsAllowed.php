<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Operator\Internal\Presenters\HowTheAllowanceChoicesRead;
use Modules\Operator\Internal\ViewModels\TheChipsAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * What a change to what may be asked for asks, as the operator fills it in before anything is sent.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesWhatIsAllowed
{
    /** The member the change is about, blank for the whole household. */
    public string $who = '';

    /** The policy as one of {@see HowTheHouseholdAsks}' words, blank to leave it. */
    public string $policy = '';

    /** How many requests a period allows, bound to its field; blank with the days to leave the limit. */
    public string $requests = '';

    /** How many days that period is, bound to its field. */
    public string $days = '';

    /** Make the change about the whole household. */
    public function forEverybody(): void
    {
        $this->who = '';
    }

    /** Make the change about the member by this name. */
    public function forSomebody(string $name): void
    {
        $this->who = $name;
    }

    /** Choose a policy by its word, or anything else to leave it. */
    public function policyIs(string $word): void
    {
        $this->policy = HowTheHouseholdAsks::tryFrom($word)->value ?? '';
    }

    /** The policy as chosen here, and every one offered. */
    public function policyChoice(): TheChipsAsShown
    {
        return new HowTheAllowanceChoicesRead()->policy(HowTheHouseholdAsks::tryFrom($this->policy));
    }
}
