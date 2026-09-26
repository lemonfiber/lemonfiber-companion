<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Handing a command over, or what came of it, said nothing where it had to say something.
 *
 * Refused rather than shown, for {@see InstructionSaysNothing}'s reason: a blank
 * command name is an act about nothing, a blank file is a row claiming something
 * was written without saying what, and a blank refusal is a failure with its
 * reason missing. Each would reach a screen as an empty line.
 */
final class HandoverSaysNothing extends InvalidArgumentException
{
    /** `$what` is the part that was blank, as a phrase: *which command*, *which file*. */
    public static function where(string $what): self
    {
        return new self(sprintf('Handing a long-running command over said nothing about %s, and a blank there reaches a screen as an empty line.', $what));
    }
}
