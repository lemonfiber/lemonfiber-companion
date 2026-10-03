<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\HouseholdWentUnread;
use Throwable;

/**
 * What stood between somebody and the household, where a reading of it ended without one.
 *
 * A household the stack said it could not read is its own obstacle: the stack
 * answered, and said why its list of people is empty. Everything else is
 * whatever stood in the way of any reach.
 */
final readonly class WhatStoodInTheWayOfTheHousehold
{
    public static function ofTheHousehold(Clients $clients, Stack $stack, Throwable $why): Obstacle
    {
        return $why instanceof HouseholdWentUnread
            ? Obstacle::of(KindOfObstacle::HouseholdCouldNotBeRead)
            : $clients->whatStoodInTheWay($stack, $why);
    }
}
