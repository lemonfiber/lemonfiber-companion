<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\WhatACapDoes;
use Modules\Operator\Internal\HowLongALiftLasts;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\TheChipsAsShown;

use function sprintf;

/**
 * The two choices a change to the line offers as chips, as the template draws them.
 */
final readonly class HowTheLineChoicesRead
{
    /** What leaving a part as it is says, on a chip and as the choice. */
    private const string LEFT_AS_IT_IS = 'stacks.line_change.left_as_it_is';

    /** What each choice of what reaching the cap does says on its chip. */
    private const string AT_THE_CAP = 'stacks.line_change.exceeded.%s';

    /** What asking for no lift says on its chip. */
    private const string NO_LIFT = 'stacks.line_change.lift.none';

    /** What reaching the cap does as chosen, each way offered, and leaving it. */
    public function exceeded(?WhatACapDoes $chosen): TheChipsAsShown
    {
        $offered = [];

        foreach (WhatACapDoes::cases() as $case) {
            $offered[] = new AChoiceOfASettingAsShown(said: sprintf(self::AT_THE_CAP, $case->value), word: $case->value, chosen: $case === $chosen);
        }

        $offered[] = new AChoiceOfASettingAsShown(said: self::LEFT_AS_IT_IS, word: '', chosen: ! $chosen instanceof WhatACapDoes);

        return new TheChipsAsShown(said: $chosen instanceof WhatACapDoes ? sprintf(self::AT_THE_CAP, $chosen->value) : self::LEFT_AS_IT_IS, offered: $offered);
    }

    /**
     * Each length a lift is offered at, the one whose minutes were typed marked, and no lift.
     *
     * @return list<AChoiceOfASettingAsShown>
     */
    public function lift(string $typed): array
    {
        $offered = [];

        foreach (HowLongALiftLasts::cases() as $case) {
            $offered[] = new AChoiceOfASettingAsShown(said: $case->saidOnTheScreen(), word: (string) $case->minutes(), chosen: HowLongALiftLasts::ofMinutes($typed) === $case);
        }

        $offered[] = new AChoiceOfASettingAsShown(said: self::NO_LIFT, word: '', chosen: $typed === '');

        return $offered;
    }
}
