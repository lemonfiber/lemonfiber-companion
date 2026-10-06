<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;
use Modules\Sdk\Api\TheStackDoesNotOfferIt;
use Throwable;

/**
 * What the operator met, given a reach that got no answer this app could read.
 *
 * {@see WhatARefusalMeant}'s other half. That one reads a stack that refused;
 * this reads every way a reach ends without an answer to read: nothing came
 * back, what came back is in an API version this app does not read, what
 * came back could not be read, or the stack does not declare the request so
 * nothing was sent. The second and the last have obstacles of their own; the
 * rest are a stack that did not answer.
 *
 * **Only what the reach itself says.** Silence here is the stack's, because
 * the reach cannot tell a switched-off machine from a phone with no network or
 * a platform that refused the app the local network. Telling those apart asks
 * the device, which is {@see \Modules\Sdk\Api\ClientsThatAskTheDevice}'s, so
 * the stand-ins in `dx`, which reach no machine at all, read silence the way a
 * stack they stand in for would cause it.
 *
 * **Two versions, both named.** A stack in another API version answered, and
 * the envelope said which version; nothing in it is read. The obstacle
 * carries both numbers, and which of the two is newer decides the remedy.
 */
final readonly class WhatTheReachMet
{
    /** What a reach that ended this way met, before the device is asked anything. */
    public static function byItself(Throwable $why): Obstacle
    {
        return match (true) {
            $why instanceof ApiVersionMismatch => self::versions($why),
            $why instanceof TheStackDoesNotOfferIt => $why->obstacle(),
            default => Obstacle::of(KindOfObstacle::StackDidNotAnswer),
        };
    }

    /** The two versions an envelope and this app speak, as the obstacle that names them. */
    public static function versions(ApiVersionMismatch $why): Obstacle
    {
        return Obstacle::versionsDisagree(TheVersionsSpoken::between($why->answered(), $why->spoken()));
    }
}
