<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\LockAfter;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;

/**
 * The phone's settings, as the rows and chips that offer them.
 *
 * `F2`: data in, view model out. Every case is offered, so a case added to an
 * enum is offered without an edit to the template.
 */
final readonly class HowThisPhoneIsSetReads
{
    /** How long the app may be away, as chosen, and every choice. */
    public function lockAfter(LockAfter $chosen): ASettingAsShown
    {
        $offered = [];

        foreach (LockAfter::cases() as $case) {
            $offered[] = new AChoiceOfASettingAsShown(said: $case->said(), word: $case->name, chosen: $case === $chosen);
        }

        return new ASettingAsShown(said: $chosen->said(), offered: $offered);
    }
}
