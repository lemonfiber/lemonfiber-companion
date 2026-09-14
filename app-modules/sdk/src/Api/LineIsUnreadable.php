<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Stream;

use function sprintf;

/**
 * A `log` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see StuckIsUnreadable} is, for `N2-R10`'s payload, and for
 * its reason: every one of these is a bug somewhere other than here, and the
 * message names the field and the position because that is the only thing that
 * shortens the search. A developer reads it, so it is `sprintf` and never
 * translated (`L1`).
 *
 * **A window is refused rather than shown short.** A log read is the thing an
 * operator turns to when the rest of the app has not explained something, and a
 * window quietly missing the line that would have explained it is worse than no
 * window at all — they would conclude the service never said it.
 */
final class LineIsUnreadable extends InvalidArgumentException
{
    public static function said(WireField $field, int $position): self
    {
        return new self(sprintf(
            'Line %d of the log window has no readable `%s`. A window with a row this app cannot read is refused rather than shown one line short, because the missing line is the one an operator went looking for.',
            $position,
            $field->value,
        ));
    }

    public static function stream(string $said, int $position): self
    {
        // The accepted list comes from the enum rather than a sentence written
        // here, so a case added to the contract cannot leave this message
        // describing the old vocabulary.
        return new self(sprintf(
            'Line %d of the log window came out of `%s`, and this app reads %s. Guessing which was meant is how a complaint gets shown as ordinary progress.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(Stream $stream): string => sprintf('`%s`', $stream->value),
                Stream::cases(),
            )),
        ));
    }

    public static function moment(string $said, int $position): self
    {
        return new self(sprintf(
            'Line %d of the log window says it happened at `%s`, which is not a moment this app can read. It is refused rather than shown without one, because a line whose time was stated and lost reads exactly like a line that never carried one.',
            $position,
            $said,
        ));
    }
}
