<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * What putting the configuration back reverts arrived with a part that cannot be shown.
 *
 * Refused rather than drawn short, because a preview is what the operator
 * agrees to: a connection with no name is a part of the reset they would agree
 * to without being told. A file it reverts is refused as
 * {@see AStackEditCannotBeShown}, wherever the stack reports one.
 */
final class ARevertCannotBeShown extends InvalidArgumentException
{
    /** A connection was named with nothing. */
    public static function anUnnamedConnection(): self
    {
        return new self('A connection putting the configuration back reverts arrived with no name, so nobody could tell which one goes back.');
    }
}
