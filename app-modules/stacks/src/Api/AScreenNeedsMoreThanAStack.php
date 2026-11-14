<?php

declare(strict_types=1);

namespace Modules\Stacks\Api;

use LogicException;

use function sprintf;

/**
 * A screen's path was asked for with the wrong number of things to put in it.
 *
 * A caller's mistake rather than anything a stack or an operator did, so a
 * `LogicException`.
 */
final class AScreenNeedsMoreThanAStack extends LogicException
{
    public static function toBeReached(AStacksScreen $screen): self
    {
        return new self(sprintf(
            '`%s` is at `%s` and naming a machine is not enough to reach it. Ask for it with what it is about as well.',
            $screen->name,
            $screen->value,
        ));
    }

    public static function andThisOneDoesNot(AStacksScreen $screen): self
    {
        return new self(sprintf(
            '`%s` is at `%s`, which names only the machine, so what it is about was handed over with nowhere in the path to put it.',
            $screen->name,
            $screen->value,
        ));
    }
}
