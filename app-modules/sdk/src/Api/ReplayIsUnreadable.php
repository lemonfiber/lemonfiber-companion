<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/** A `replay` envelope did not hold what the contract says one holds. */
final class ReplayIsUnreadable extends InvalidArgumentException
{
    /** The field saying whether every alert follows is absent, or not a word. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The replay envelope has no readable `%s`. What follows is read as missing some alerts.',
            $field->value,
        ));
    }
}
