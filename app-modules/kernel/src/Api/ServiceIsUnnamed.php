<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A finding says it is about a service and does not say which.
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
}
