<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * An origin arrived that names nothing, so it attributes nothing.
 *
 * The sibling of {@see SettingIsUnnamed}, for the other half of a settings
 * row, and the same refusal wherever else an origin is read — a check, or a
 * service reaching somewhere. Both arms of {@see WhoPutItThere} that carry a
 * string need it: a plugin attribution without the plugin, and an unknown
 * origin without the reason it is unknown, are each a sentence that stops
 * before the part the operator came for.
 *
 * Raised rather than softened to {@see WhoPutItThere::unknown()},
 * which would be the tempting repair and is the wrong one. *Unknown* is a
 * thing a stack says about a value; it is not a place to put this app's own
 * failures to read, and using it as one would make the arm that exists to keep
 * *unknown* honest the arm that absorbs every defect.
 */
final class AnOriginIsUnnamed extends InvalidArgumentException
{
    public static function fromAPlugin(): self
    {
        return new self('something was attributed to a plugin with no name, and an attribution nobody can read attributes nothing');
    }

    public static function unknownForNoStatedReason(): self
    {
        return new self('an origin was reported unknown with no reason, and *unknown* with nothing after it reads as a default');
    }

    public static function replacingABlank(): self
    {
        return new self('a plugin was said to have replaced a blank value, and a value with nothing in it is the stack saying nothing was set');
    }
}
