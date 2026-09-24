<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `glossary` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 */
final class GlossaryIsUnreadable extends InvalidArgumentException
{
    /** The list of words is absent or not a list. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The glossary envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One word is not what the contract says a word is. */
    public static function word(int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Word %d of the glossary has no readable `%s`. A word that does not say what it means is not drawn half-explained.',
            $position,
            $field->value,
        ));
    }
}
