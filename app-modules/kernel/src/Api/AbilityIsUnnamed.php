<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A capability set carried something with no name.
 *
 * Refused where the set is read rather than where a screen asks about it. An
 * unnamed capability cannot be matched against anything, so every later question
 * about it answers "not present" — which `ARCH-R79` reserves for a stack that
 * genuinely does not have the capability, and this is a stack that does and
 * could not say so.
 */
final class AbilityIsUnnamed extends InvalidArgumentException
{
    public static function inACapabilitySet(): self
    {
        return new self('A capability set named something with an empty string, so nothing can be matched against it and every question about it would answer that the stack cannot do it.');
    }
}
