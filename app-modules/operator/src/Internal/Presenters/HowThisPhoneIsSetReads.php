<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\LockAfter;
use Modules\Operator\Internal\NotACountOfDays;
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
    /** The catalogue key a count of days is said by. */
    private const string DAYS = 'settings.days';

    /** How long the app may be away, as chosen, and every choice. */
    public function lockAfter(LockAfter $chosen): ASettingAsShown
    {
        $offered = [];

        foreach (LockAfter::cases() as $case) {
            $offered[] = new AChoiceOfASettingAsShown(said: $case->said(), word: $case->name, chosen: $case === $chosen);
        }

        return new ASettingAsShown(said: $chosen->said(), offered: $offered);
    }

    /**
     * How long readings are kept, as chosen, and every choice.
     *
     * A count of days that is not one offered by name is drawn as that count,
     * with Other… as the chip in force; while the operator is typing one, Other…
     * is the chip in force whatever is kept.
     */
    public function keepReadings(HowLongReadingsAreKept $kept, bool $typingDays): ASettingAsShown
    {
        $offered = [];
        $named = false;

        foreach (KeptFor::cases() as $case) {
            $chosen = ! $typingDays && $kept->is($case);
            $named = $named || $kept->is($case);
            $offered[] = new AChoiceOfASettingAsShown(said: $this->saidFor($case), word: $case->name, chosen: $chosen, count: $case->value);
        }

        $untilRemoved = $kept->isUntilRemoved();

        $offered[] = new AChoiceOfASettingAsShown(
            said: NotACountOfDays::UntilRemoved->said(),
            word: NotACountOfDays::UntilRemoved->value,
            chosen: ! $typingDays && $untilRemoved,
        );
        $offered[] = new AChoiceOfASettingAsShown(
            said: NotACountOfDays::Other->said(),
            word: NotACountOfDays::Other->value,
            chosen: $typingDays || (! $named && ! $untilRemoved),
        );

        return $kept->either(
            days: fn(int $days): ASettingAsShown => new ASettingAsShown(
                said: $kept->is(KeptFor::OneYear) ? $this->saidFor(KeptFor::OneYear) : self::DAYS,
                offered: $offered,
                count: $days,
            ),
            untilRemoved: static fn(): ASettingAsShown => new ASettingAsShown(said: NotACountOfDays::UntilRemoved->said(), offered: $offered),
        );
    }

    /** The catalogue key a length offered by name is said by: a year by name, the rest as days. */
    private function saidFor(KeptFor $kept): string
    {
        return $kept === KeptFor::OneYear ? 'settings.one_year' : self::DAYS;
    }
}
