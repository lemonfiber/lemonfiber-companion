<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Taking somebody out arrived with a word it owes left blank, or a count below none.
 *
 * Refused rather than shown: a finding with nothing in it is a warning the
 * stack admits to and will not say, and a count of requests below none is not
 * a count of anything.
 */
final class RemovalSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because there are several. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'Taking somebody out of the household arrived with its `%s` blank, and a removal that will not say it is not one to act on.',
            $field,
        ));
    }

    /** A figure that cannot be fewer than none was. */
    public static function below(string $field, int $said): self
    {
        return new self(sprintf(
            'Taking somebody out of the household arrived with its `%s` at %d, which is fewer than none.',
            $field,
            $said,
        ));
    }
}
