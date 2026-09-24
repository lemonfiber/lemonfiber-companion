<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * An update was asked for from a reading that offered none.
 *
 * A `LogicException` because no payload produces it: a screen offers an update
 * only where {@see Upkeep::hasSomethingToOffer()} said yes, so reaching this is
 * a caller that skipped the question.
 */
final class NothingToTake extends LogicException
{
    public static function from(AgainstThePins $pins): self
    {
        return new self(sprintf(
            'The stack said its services are `%s` and offered nothing to take, so no update can be agreed to.',
            $pins->value,
        ));
    }
}
