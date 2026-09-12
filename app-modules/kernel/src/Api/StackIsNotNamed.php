<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stack was paired and given nothing to be called.
 *
 * Refused rather than filled in with an address, which is the tempting default
 * and is wrong twice over: `N1-R15` keeps a stack address off every screen, and
 * an operator with two stacks needs to tell them apart by something they chose.
 * "192.168.1.42" and "192.168.1.43" are not two names.
 */
final class StackIsNotNamed extends InvalidArgumentException
{
    public static function afterPairing(): self
    {
        return new self('A stack was paired with no name, and a name is the only thing an operator has to tell two of them apart by.');
    }
}
