<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A support bundle was said to be written to a path that names no file.
 *
 * Refused where the path becomes an {@see AWrittenBundle}: the file is fetched
 * by the path's last segment, and a path ending in nothing, or in a step up the
 * tree, would fetch something other than the bundle.
 */
final class ABundleHasNoName extends InvalidArgumentException
{
    public static function whereItWasWritten(): self
    {
        return new self('A written support bundle has to be at a path ending in a file name. One ending in nothing, `.` or `..` would be fetched as something other than the bundle.');
    }
}
