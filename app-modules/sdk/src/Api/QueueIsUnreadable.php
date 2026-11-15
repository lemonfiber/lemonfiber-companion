<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * The `queue` envelope did not hold what the contract says it holds.
 *
 * Refused rather than drawn as something else: a rehearsal drawn as items
 * cleared, or three acts drawn as one, is worse than none. A developer reads
 * it, so it is `sprintf` and never translated.
 */
final class QueueIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The queue envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word it has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The queue envelope says `%s` is `%s`, and this app reads %s. Reading it as the nearest one could draw an act that was refused as one that was done.',
            $field->value,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
