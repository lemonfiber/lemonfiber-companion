<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What one line of the machine's accounting is about.
 *
 * The categories the stack gives. The room is shown by these and never as a
 * list of files.
 */
enum WhatALineIsAbout: string
{
    /** One directory beneath the data location, named as the operator named it. */
    case Tree = 'tree';

    /** What the download clients still have to write. */
    case Landing = 'landing';

    /** Completed downloads still being seeded. */
    case Seeding = 'seeding';

    /** Downloads on disk that no service ever took. */
    case Orphaned = 'orphaned';

    /** Archives whose contents are unpacked beside them. */
    case Extracted = 'extracted';

    /** The services' own configuration and databases. */
    case Services = 'services';

    /** What the operator said to leave alone. */
    case Unmanaged = 'unmanaged';

    /** The catalogue key for this, as an operator reads it. A tree's takes its name. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.room.about.%s', $this->value);
    }
}
