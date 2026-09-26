<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A quality choice, or an answer about one, has a word it owes left blank.
 *
 * Refused rather than drawn or sent: a choice with no name, or a cost with no
 * figure, is one an operator would be deciding against without knowing what
 * it is.
 */
final class QualitySaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a choice carries several. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A quality choice has its `%s` blank, and a choice that will not say what it is is not one to decide against.',
            $field,
        ));
    }
}
