<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where one completed download on the machine stands.
 *
 * Three answers that must never be drawn as each other: one removes at no
 * cost, one costs standing with a tracker, and one somebody asked to be left.
 */
enum WhereADownloadStands: string
{
    /** Nothing ever took it into a library. */
    case NeverImported = 'never_imported';

    /** It was taken in and is still being seeded. */
    case Seeding = 'seeding';

    /** The operator asked for it to be left alone. */
    case LeftAlone = 'left_alone';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.room.standing.%s', $this->value);
    }
}
