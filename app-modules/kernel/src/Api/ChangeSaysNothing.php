<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * One change arrived saying less than a row about it needs.
 *
 * Refused rather than shown, for {@see UnattendedIsUnnamed}'s reason one
 * surface along. The record is the one place an operator goes to find out what
 * was done to their machine while they were not looking, and a row with a
 * blank where *what it did* belongs is a change the record admits happened and
 * will not describe.
 */
final class ChangeSaysNothing extends InvalidArgumentException
{
    /**
     * One of its words was blank.
     *
     * The field is named because the record can be long, and a refusal naming
     * nothing is the defect it is refusing.
     */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A change arrived with its `%s` blank, and a record row that admits something was done and will not say what is worse than no row.',
            $field,
        ));
    }

    /**
     * It claimed fewer companions than it has.
     *
     * The count is how many changes the one operation made *with this one
     * among them*, so it cannot be below one. A row saying it came alone when
     * it did not is the row that gets undone by itself, leaving half an
     * operation nobody chose.
     */
    public static function alone(int $said): self
    {
        return new self(sprintf(
            'A change says the operation that made it made %d changes, and it is one of them. Undoing one line of an operation that made more leaves a machine in a state nobody chose, so a count that cannot be true is refused rather than shown.',
            $said,
        ));
    }
}
