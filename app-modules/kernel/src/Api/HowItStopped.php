<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which kind of stopped a stopped item is, in the stack's words and its order.
 *
 * Seven, because each is a different thing to do: an item fetched over and
 * over wants the fetching stopped, one that finished and was never imported
 * wants its import looked at, and one that is only slow wants nothing but
 * time. The cases are declared worst first, which is the order the stack sends
 * them in, and nothing here sorts by them: a list is shown in the order it came.
 *
 * **Slow is not stuck.** It is on the list because the stack has been watching
 * it, and it needs patience rather than a fix. Shown among the stuck it teaches
 * an operator to read past the whole list, so {@see self::wantsAFix()} is what
 * a screen asks before it decides which heading a row goes under.
 */
enum HowItStopped: string
{
    case RedownloadLoop = 'redownload-loop';
    case RepeatedImportFailure = 'repeated-import-failure';
    case CompletedNotImported = 'completed-not-imported';
    case Orphaned = 'orphaned';
    case StalledDownload = 'stalled-download';
    case WaitingIndefinitely = 'waiting-indefinitely';
    case Slow = 'slow';

    /** The key for what this kind of stopped means, said plainly. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.stopped.%s', $this->value);
    }

    /** Whether somebody has to do something, which slow is the one kind that does not ask. */
    public function wantsAFix(): bool
    {
        return $this !== self::Slow;
    }
}
