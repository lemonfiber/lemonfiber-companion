<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How far something got before it stopped.
 *
 * Stuck downloads must be reachable, and *stuck* on its own is not
 * a thing anybody can act on. A title that never found a release and a title
 * sitting fully downloaded waiting to be imported are the same word to an
 * operator and two different afternoons: one is an indexer that has nothing,
 * the other is a file the library never picked up. The stage is the whole
 * difference, which is why it is carried rather than flattened into *stuck*.
 *
 * **An enum, because this set is closed by the contract** rather than by this
 * app — `stuck.items[].stage` lists exactly these ten, so `D4` gets its enum
 * and a stage this build has not heard of is a contract change rather than a
 * value to pass through. That is the opposite of {@see Ability}, where the
 * stack declares the set at runtime and an enum would be this build's guess at
 * what exists.
 *
 * **Declared in the order the work happens**, which is the order an operator
 * reads them in and the order they are listed in the contract. The position is
 * meaning here: two titles stuck at different stages are not equally far along,
 * and a screen sorting them alphabetically would put *available* above
 * *searching* and tell somebody the finished one needs attention first.
 */
enum Stage: string
{
    /** Nothing is watching for it, so nothing will ever fetch it. */
    case NotMonitored = 'not-monitored';

    /** Watched for, and not looked for yet. */
    case Monitored = 'monitored';

    /** Being looked for, with nothing found so far. */
    case Searching = 'searching';

    /** A release exists and has not been taken. */
    case Found = 'found';

    /** Taken, and not started coming down. */
    case Grabbed = 'grabbed';

    /** Coming down now. */
    case Downloading = 'downloading';

    /** Down, and not yet handed to the library. */
    case Downloaded = 'downloaded';

    /** Being handed over. */
    case Importing = 'importing';

    /** Handed over, and not yet playable. */
    case Imported = 'imported';

    /** There, and playable. */
    case Available = 'available';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. Nested under `health.stage.` beside the groups already there,
     * and the hyphen in a value is carried into the key rather than smoothed
     * out: `Waiting` already does that, and a second spelling rule for the same
     * wire shape is the thing that drifts.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.stage.%s', $this->value);
    }

    /**
     * Whether anything is still going to happen to this by itself.
     *
     * The one decision that belongs here rather than on a screen, because the
     * two ends of this list are not the same problem. `NotMonitored` and
     * `Available` are both terminal — nothing is watching, or there is nothing
     * left to watch for — and everything between them is work that has stalled
     * part-way and can be nudged.
     *
     * A screen that worked this out for itself would be a second copy of where
     * the pipeline ends, and the first stage added to the contract would make
     * the two disagree.
     */
    public function stillMoving(): bool
    {
        return match ($this) {
            self::NotMonitored, self::Available => false,
            self::Monitored, self::Searching, self::Found, self::Grabbed,
            self::Downloading, self::Downloaded, self::Importing, self::Imported => true,
        };
    }
}
