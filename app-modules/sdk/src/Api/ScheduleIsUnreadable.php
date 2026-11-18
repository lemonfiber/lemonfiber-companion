<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Lemonfiber\Sdk\Generated\Kind;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * A run-and-exit service's reading, on the `status` envelope or the `ran` one, did not hold what the contract says it holds.
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

    /** A field the `ran` envelope needs is missing or not what the contract says. */
    public static function ran(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The ran envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A field a run needs is missing or not what the contract says, on the envelope of this kind. */
    public static function aRun(Kind $kind, NamesAWireField $field): self
    {
        return new self(sprintf(
            'The %s envelope has a run-and-exit service\'s run with no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $kind->value,
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
