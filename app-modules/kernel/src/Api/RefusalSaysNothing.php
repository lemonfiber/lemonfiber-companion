<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A stack's refusal arrived with a word it owes left blank.
 *
 * Refused rather than drawn: a refusal with no sentence in it tells the
 * operator that something was refused and nothing about what to do next.
 */
final class RefusalSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named so the failure says which. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A refusal arrived with its `%s` blank, and a refusal that will not say why is no answer.',
            $field,
        ));
    }
}
