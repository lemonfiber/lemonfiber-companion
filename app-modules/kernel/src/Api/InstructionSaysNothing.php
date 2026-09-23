<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A machine with no manager said nothing about what to do instead.
 *
 * Refused rather than shown, which is {@see RemedySaysNothing}'s argument.
 * *Not available here* reads differently from *off* only because of the
 * sentence beside it. A blank one leaves the screen drawing the empty box it
 * draws for off — the reading this whole arm exists to prevent — and it does it
 * while looking like the arm was taken.
 */
final class InstructionSaysNothing extends InvalidArgumentException
{
    public static function whereThereIsNoManager(): self
    {
        return new self('A machine with no service manager said nothing about what to do instead, and *not available here* with no sentence beside it is the empty box that reads as *off*.');
    }
}
