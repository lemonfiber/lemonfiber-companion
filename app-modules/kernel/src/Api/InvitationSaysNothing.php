<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * An invitation, or what one is asked with, arrived with a word it owes left blank.
 *
 * Refused rather than shown: a name with nothing in it is nobody, and an
 * address with nothing in it is one somebody would be sent to and find
 * nothing at.
 */
final class InvitationSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because there are several. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'An invitation arrived with its `%s` blank, and an invitation that will not say it is not one to hand anybody.',
            $field,
        ));
    }

    /** A figure that cannot be fewer than none was. */
    public static function below(string $field, int $said): self
    {
        return new self(sprintf(
            'An invitation arrived with its `%s` at %d, which is fewer than none.',
            $field,
            $said,
        ));
    }
}
