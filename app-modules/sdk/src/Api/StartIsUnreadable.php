<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * The `boot` envelope did not hold what the contract says it holds.
 *
 * Refused rather than drawn as something else: a check read as having run
 * when it had not, or as having passed when it did not, is worse than none. A
 * developer reads it, so it is `sprintf` and never translated.
 */
final class StartIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The boot envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word it has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The boot envelope says `%s` is `%s`, and this app reads %s. Reading it as the nearest one could draw a check that failed as one that passed.',
            $field->value,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
