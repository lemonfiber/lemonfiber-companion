<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\TheMembers;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\TheChipsAsShown;
use Modules\Operator\Internal\ViewModels\WhoToChooseAsShown;

/**
 * The choices a change to what may be asked for offers, as the template draws them.
 */
final readonly class HowTheAllowanceChoicesRead
{
    /** What leaving the policy as it is says, on a chip and as the choice. */
    private const string LEFT_AS_IT_IS = 'stacks.allowance_change.policy.left_as_it_is';

    /** Each policy the stack offers, the one chosen marked, and leaving it. */
    public function policy(?HowTheHouseholdAsks $chosen): TheChipsAsShown
    {
        $offered = [];

        foreach (HowTheHouseholdAsks::cases() as $case) {
            $offered[] = new AChoiceOfASettingAsShown(said: $case->saidOnTheScreen(), word: $case->value, chosen: $case === $chosen);
        }

        $offered[] = new AChoiceOfASettingAsShown(said: self::LEFT_AS_IT_IS, word: '', chosen: ! $chosen instanceof HowTheHouseholdAsks);

        return new TheChipsAsShown(said: $chosen instanceof HowTheHouseholdAsks ? $chosen->saidOnTheScreen() : self::LEFT_AS_IT_IS, offered: $offered);
    }

    /** Each member listed, by name, in the stack's order. */
    public function members(TheMembers $members): WhoToChooseAsShown
    {
        $names = [];

        foreach ($members as $member) {
            $names[] = $member->name();
        }

        return new WhoToChooseAsShown(members: $names, membersRead: true);
    }

    /** Only the whole household, where the members could not be read. */
    public function onlyEverybody(): WhoToChooseAsShown
    {
        return new WhoToChooseAsShown(members: [], membersRead: false);
    }
}
