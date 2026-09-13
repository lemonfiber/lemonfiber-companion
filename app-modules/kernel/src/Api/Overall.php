<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

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

    /**
     * What this verdict is called at the top of a screen, as a key.
     *
     * Built from the case rather than listed against it, and under
     * `health.overall.` because that group already holds a line per case. A
     * `match` here would spell every stem twice — once as the case's value and
     * once as the string beside it — and two spellings of one name drift.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.overall.%s', $this->value);
    }
}
