<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What stood in the way of finding the house from what a phone was handed, in the household's words.
 */
enum WhatFindingTheHouseMet: string
{
    case CodeUnreadable = 'code_unreadable';

    case LinkUnusable = 'link_unusable';

    case NotThisHouse = 'not_this_house';

    case NotKept = 'not_kept';

    /** The key for what stood in the way. */
    public function said(): string
    {
        return InTheWayInsWords::said($this->value);
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return match ($this) {
            self::LinkUnusable, self::NotThisHouse => InTheWayInsWords::askingForANewInvitation(),
            self::CodeUnreadable, self::NotKept => InTheWayInsWords::remedy($this->value),
        };
    }
}
