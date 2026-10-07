<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Availability;

use function sprintf;

/**
 * The `capabilities` envelope did not hold what the contract says it holds.
 *
 * The refusal {@see RosterIsUnreadable} is, for what a stack declares it can
 * do. A developer reads it, so it is `sprintf` and never translated, and the
 * words it accepts come from the enum rather than being written out.
 */
final class CapabilitiesAreUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The capabilities envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One path was said to be in a state none of the contract's words name. */
    public static function state(string $path): self
    {
        return new self(sprintf(
            'The capabilities envelope says %s is in a state that is none of %s. It is refused rather than guessed at: a button drawn from a word nobody can read is a guess about whether it works.',
            $path,
            implode(', ', array_map(static fn(Availability $said): string => $said->value, Availability::cases())),
        ));
    }
}
