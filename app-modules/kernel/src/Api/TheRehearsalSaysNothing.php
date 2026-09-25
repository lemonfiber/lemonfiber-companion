<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A form's rehearsal, or what it left out, arrived with a field that cannot be. */
final class TheRehearsalSaysNothing extends InvalidArgumentException
{
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What a form would start or left out arrived with its `%s` blank or below zero, and cannot be drawn as the stack meant it.',
            $field,
        ));
    }
}
