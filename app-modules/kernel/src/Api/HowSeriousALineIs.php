<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The severity a log line declared for itself, as the core normalised it.
 *
 * Services announce severity each their own way — `[Warn]`, `WARN`,
 * `level=error` — and the core reads what it confidently can into these six,
 * leaving a line it cannot place without one. So a line either carries one of
 * these or none, and none is not *info*: it is a line nobody classified.
 *
 * **It is not the stream.** {@see Stream} is which file descriptor a process
 * wrote to, and plenty of services write progress to `stderr`. This is what
 * the line said about itself, which is what a mark on a screen can rest on.
 */
enum HowSeriousALineIs: string
{
    case Trace = 'trace';

    case Debug = 'debug';

    case Info = 'info';

    case Warn = 'warn';

    case Error = 'error';

    case Fatal = 'fatal';

    /** Whether the line says something went wrong: an error, or one the service did not survive. */
    public function isAnError(): bool
    {
        return $this === self::Error || $this === self::Fatal;
    }

    /** Whether the line warns: nothing has gone wrong yet, and it may. */
    public function isAWarning(): bool
    {
        return $this === self::Warn;
    }

    /** What this is called on a screen, as a key under `health.level.`. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.level.%s', $this->value);
    }
}
