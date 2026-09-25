<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A rehearsal of a start arrived with a field blank that says what it is about. */
final class TheRehearsalSaysNothing extends InvalidArgumentException
{
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A rehearsal of a start arrived with its `%s` blank, and a profile named as nothing cannot be said to be left out.',
            $field,
        ));
    }
}
