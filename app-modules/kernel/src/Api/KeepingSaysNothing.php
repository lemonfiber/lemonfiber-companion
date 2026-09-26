<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Something the stack keeps, or a copy it holds, arrived with a word blank.
 *
 * Refused rather than drawn, because every word here is one an operator acts
 * on: a kept thing with no location is a thing they cannot find, one with no
 * reason is one they cannot judge, and a copy with no name is one they could
 * never ask to have put back.
 */
final class KeepingSaysNothing extends InvalidArgumentException
{
    /** The field is named, because a machine keeps many things and a refusal naming none is no help. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'Something this machine keeps arrived with its `%s` blank, and an entry that will not say what it is reads as complete to somebody deciding what to keep.',
            $field,
        ));
    }

    /** A figure below nothing, which no measurement of a copy could come to. */
    public static function below(string $field, int $figure): self
    {
        return new self(sprintf(
            'A copy arrived with its `%s` at %d, and a size below nothing is a stack that measured nothing.',
            $field,
            $figure,
        ));
    }
}
