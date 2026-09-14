<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Something says it is about a service and does not say which.
 *
 * Refused rather than shown, for the reason {@see CheckIsUnnamed} gives: a name
 * nobody can read is a name that cannot be matched against the stack, and an
 * operator shown a blank where a service belongs learns less than one shown
 * nothing at all.
 */
final class ServiceIsUnnamed extends InvalidArgumentException
{
    public static function onAFinding(): self
    {
        return new self(
            'A finding says it is about a service and names none, so nothing could be shown about which.',
        );
    }

    /**
     * A service was named where one is the whole point of the call.
     *
     * Its own sentence rather than {@see self::onAFinding()}'s, because the two
     * are different faults: a finding naming no service is an engine that let a
     * field go, and this is a caller asking to read the logs of nothing.
     */
    public static function whereOneWasExpected(): self
    {
        return new self(
            'A service was named as nothing at all, and a window over no service is the whole machine talking at once.',
        );
    }
}
