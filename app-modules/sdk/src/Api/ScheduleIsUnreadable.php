<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * The `status` envelope's run-and-exit services did not hold what the contract says they hold.
 */
final class ScheduleIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The status envelope has a run-and-exit service with no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word it has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The status envelope says a run-and-exit service\'s `%s` is `%s`, and this app reads %s.',
            $field->value,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
