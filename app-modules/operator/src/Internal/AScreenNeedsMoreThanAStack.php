<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use LogicException;

use function sprintf;

/**
 * A screen's path was built with the wrong number of things to put in it.
 *
 * A `LogicException` rather than an `InvalidArgumentException`, and the
 * distinction is the point: nothing a stack or an operator does can produce
 * this. It is a caller asking {@see AStacksScreen} for a path in a way the case
 * it named cannot answer, which is a mistake in this module and nowhere else.
 *
 * It exists because the alternative is silent. `str_replace` handed a pattern
 * with a placeholder it was not given leaves the placeholder in the string, and
 * a path reading `/stacks/abc/logs/{service}` resolves to nothing — a button
 * that does nothing, on a handset, with no error anywhere. That is the exact
 * failure {@see AStacksScreen} was written to end, and a second road to it is
 * worth a refusal rather than a comment.
 */
final class AScreenNeedsMoreThanAStack extends LogicException
{
    public static function toBeReached(AStacksScreen $screen): self
    {
        return new self(sprintf(
            '`%s` is at `%s` and naming a machine is not enough to reach it. Ask for it with the service as well.',
            $screen->name,
            $screen->value,
        ));
    }

    public static function andThisOneDoesNot(AStacksScreen $screen): self
    {
        return new self(sprintf(
            '`%s` is at `%s`, which names no service, so a service was handed over with nowhere in the path to put it.',
            $screen->name,
            $screen->value,
        ));
    }
}
