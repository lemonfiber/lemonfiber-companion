<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A setting arrived with no name, so nothing can be said about it.
 *
 * A setting is a name and what it holds. Without the name there is no row to
 * draw and nothing to change later — the value would be a string on a screen
 * with no way to say what it is the value *of*.
 *
 * Raised rather than skipped. A reading that dropped the nameless one would
 * show the operator a shorter list than the stack sent and say nothing about
 * the difference, which is the silent subset the whole screen exists to
 * avoid.
 */
final class SettingIsUnnamed extends InvalidArgumentException
{
    public static function inTheListing(): self
    {
        return new self('a setting arrived with no name, and a setting is a name and what it holds');
    }
}
