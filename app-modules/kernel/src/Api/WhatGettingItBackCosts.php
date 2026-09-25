<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What getting one line's room back would cost, as the stack says it.
 *
 * A property of what the line is about, decided by the stack. This app shows
 * it beside the line and offers none of it.
 */
enum WhatGettingItBackCosts: string
{
    /** Only by losing something somebody chose to keep. */
    case ByLosingContent = 'by_losing_content';

    /** Nothing to get back: it is being written now. */
    case InProgress = 'in_progress';

    /** It can be had, at the cost of standing with the trackers it came from. */
    case AtTheCostOfRatio = 'at_the_cost_of_ratio';

    /** It can be had, and costs nothing. */
    case TheEasyWin = 'the_easy_win';

    /** It can be had: the unpacked copy is the one in use. */
    case AlreadyHaveIt = 'already_have_it';

    /** A little, and rarely worth it. */
    case Marginally = 'marginally';

    /** Not at all, because the operator said so. */
    case YouSaidNot = 'you_said_not';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.room.reclaim.%s', $this->value);
    }
}
