<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Operator\Internal\ViewModels\AnUnratedChoiceAsShown;
use Modules\Operator\Internal\ViewModels\TheUnratedChoiceAsShown;

/**
 * What becomes of unrated material, as the choice an invitation is asked with.
 *
 * `F2`: data in, view model out. Every case of {@see WhatBecomesOfUnrated} is
 * offered, and leaving it to the stack after them, so a case added to the
 * enum is offered without an edit to the template.
 */
final readonly class HowTheUnratedChoiceReads
{
    /** What leaving it to the stack is called, where nothing is chosen. */
    private const string LEFT_TO_THE_STACK = 'stacks.invitation.unrated.left_to_the_stack';

    /** The words that offer leaving it to the stack. */
    private const string LEAVE_IT = 'stacks.invitation.leave_unrated_to_the_stack';

    /** The choice as it stands, where nothing chosen leaves it to the stack. */
    public function chosen(?WhatBecomesOfUnrated $chosen): TheUnratedChoiceAsShown
    {
        $offered = [];

        foreach (WhatBecomesOfUnrated::cases() as $case) {
            $offered[] = new AnUnratedChoiceAsShown(said: $this->offering($case), word: $case->value, chosen: $case === $chosen);
        }

        $offered[] = new AnUnratedChoiceAsShown(said: self::LEAVE_IT, word: '', chosen: !$chosen instanceof WhatBecomesOfUnrated);

        return new TheUnratedChoiceAsShown(
            said: $chosen?->saidOnTheScreen() ?? self::LEFT_TO_THE_STACK,
            offered: $offered,
        );
    }

    /** The catalogue key for the words that offer this case. */
    private function offering(WhatBecomesOfUnrated $case): string
    {
        return match ($case) {
            WhatBecomesOfUnrated::HeldBack => 'stacks.invitation.hold_unrated_back',
            WhatBecomesOfUnrated::LetThrough => 'stacks.invitation.let_unrated_through',
        };
    }
}
