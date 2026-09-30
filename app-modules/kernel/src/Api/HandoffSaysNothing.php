<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A hand-off arrived with a word it owes left blank.
 *
 * Refused rather than shown: an app with no name, or a code with nothing in it,
 * is something a person would be sent to look for and never find.
 */
final class HandoffSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a refusal naming nothing is the defect. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A hand-off arrived with its `%s` blank, and a hand-off that will not say it is not one to give somebody.',
            $field,
        ));
    }
}
