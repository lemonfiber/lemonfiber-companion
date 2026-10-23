<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A check that disturbs something arrived with a word it owes left blank, or a bound below none.
 *
 * Refused rather than offered: a check that will not say what it disturbs,
 * or that names nothing to run, is not one an operator can agree to.
 */
final class ADisturbanceSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because it carries two. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A check that disturbs something arrived with its `%s` blank, and a disturbance that will not say what it is is not one to agree to.',
            $field,
        ));
    }

    /** Its bound was below none. */
    public static function below(int $seconds): self
    {
        return new self(sprintf('A check that disturbs something arrived bounded to %d seconds, which is fewer than none.', $seconds));
    }
}
