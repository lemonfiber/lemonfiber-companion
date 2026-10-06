<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;

/**
 * What a member is told stood in the way, in the house's words.
 *
 * The obstacle's own sentences, except where they are about the machine: a
 * house too old for what a member opened is said as the house needing an
 * update, because a member is never told which software runs it or which
 * version, and updating it is not theirs to do.
 */
final readonly class WhatAMemberIsTold
{
    /** The catalogue key for what happened. */
    public static function met(Obstacle $why): string
    {
        return $why->is(KindOfObstacle::NotOnThisStack) ? 'household.needs_an_update' : $why->said();
    }

    /** The catalogue key for what to do about it. */
    public static function remedy(Obstacle $why): string
    {
        return $why->is(KindOfObstacle::NotOnThisStack) ? 'household.needs_an_update_action' : $why->remedy();
    }
}
