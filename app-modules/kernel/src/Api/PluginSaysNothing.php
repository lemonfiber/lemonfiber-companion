<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Something about a plugin arrived with a word it owes left blank.
 *
 * Refused rather than shown: a plugin with no name, a change with no path or a
 * value with nowhere to go is something the operator would be agreeing to
 * without being told what.
 */
final class PluginSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because there are many. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A plugin arrived with its `%s` blank, and a plugin that will not say what it is or does is not one to agree to.',
            $field,
        ));
    }
}
