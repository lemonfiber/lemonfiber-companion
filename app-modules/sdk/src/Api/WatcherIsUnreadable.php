<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * The `watcher` envelope did not hold what the contract says it holds.
 *
 * Refused rather than drawn as something else: a monitor that stopped
 * reporting drawn as a service that is down, or the reverse, is worse than
 * none. A developer reads it, so it is `sprintf` and never translated.
 */
final class WatcherIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The watcher envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word it has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The watcher envelope says `%s` is `%s`, and this app reads %s. Reading it as the nearest one could draw a monitor that stopped reporting as a service that is down.',
            $field->value,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
