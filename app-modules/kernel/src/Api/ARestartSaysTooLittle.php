<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What the stack said of its last start arrived saying less than it needs to, or more than it may.
 *
 * Refused rather than shown: a check drawn against a start the machine never
 * named would read as one that ran after the latest, and a check that has not
 * run since the last restart must never be shown as passing.
 */
final class ARestartSaysTooLittle extends InvalidArgumentException
{
    /** One of its words was blank, named because a refusal naming nothing is the defect. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What the stack said of its last start arrived with its `%s` blank, and a blank reads as nothing to say.',
            $field,
        ));
    }

    /** A check said to be against the latest start, from a machine that will not say when it started. */
    public static function againstAStartNobodyNamed(): self
    {
        return new self('What the stack said of its last start carries a check against the latest start, and no time for that start. Drawn, it would read as a check after the latest restart that nobody can place.');
    }
}
