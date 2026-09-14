<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A form was named as nothing at all.
 *
 * Refused where the string becomes a {@see Form}, for
 * {@see ServiceIsUnnamed}'s reason: a name nobody can read is a name that
 * cannot be matched against the stack, and a heading with nothing in it is a
 * group an operator is invited to stop without being told what is in it.
 */
final class FormIsUnnamed extends InvalidArgumentException
{
    public static function whereOneWasExpected(): self
    {
        return new self(
            'A form was named as nothing at all, and a form nobody can name is a heading an operator would be asked to act on blind.',
        );
    }
}
