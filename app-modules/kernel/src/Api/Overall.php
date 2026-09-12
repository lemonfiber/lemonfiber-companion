<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a run's findings amount to, as one word.
 *
 * The server sends it rather than leaving it to be derived, and taking it as
 * sent is the point: a screen that worked it out from the findings would be a
 * second implementation of a judgement the engine already made, and the two
 * would disagree the first time a check was added that only one of them knew
 * how to weigh.
 *
 * **`Unknown` is not a middle ground between degraded and healthy.** It is the
 * distinction `Conclusion::Unverified` draws, one level up: something could not
 * be determined, so health is not a settled fact. Ranking it as "mildly
 * degraded" would let a run that failed to check the tunnel read as a run that
 * checked it and found a warning.
 *
 * Declared worst first, like `Conclusion`, so the two read the same way in a
 * file and neither needs a number beside it to say which is worse.
 */
enum Overall: string
{
    /** Something is broken. */
    case Broken = 'broken';

    /** Something could not be determined, so health is not a settled fact. */
    case Unknown = 'unknown';

    /** Warnings, but nothing broken. */
    case Degraded = 'degraded';

    /** Everything that ran passed. */
    case Healthy = 'healthy';
}
