<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A long-running command arrived with one of its three words blank.
 *
 * Refused rather than shown, for the reason {@see ServiceIsUnnamed} gives. The
 * row this would draw carries a control that installs something, and the worst
 * form of it is a row offering to make a blank survive every reboot.
 */
final class UnattendedIsUnnamed extends InvalidArgumentException
{
    /**
     * One word was blank, named by whichever of the others was not.
     *
     * The neighbour is carried because a refusal naming nothing is the defect
     * it is refusing — a listing of nine commands failing with *one of these
     * says nothing* leaves somebody reading all nine.
     */
    public static function beside(string $other): self
    {
        return new self(sprintf(
            'A long-running command arrived beside `%s` with its name, its command or what it guarantees left blank, and a row offering to keep a blank running past every reboot is worse than no row.',
            $other,
        ));
    }
}
