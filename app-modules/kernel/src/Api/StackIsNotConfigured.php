<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use RuntimeException;

use function sprintf;

/**
 * Something asked this device about a stack it has not been introduced to.
 *
 * Raised rather than answered with, which is this module's exception rather
 * than its rule. The last clause is that a reading from one stack must
 * never be attributed to another, and the way that happens is a lookup which
 * misses and falls back — to the first stack, to the only stack, to whatever a
 * previous screen left behind. There is no stack to carry on with here, so
 * there is nothing for a caller to do with a value except stop.
 *
 * Its own type rather than {@see StackIsUnidentified},
 * which is about retained state naming a stack with nothing. The two read the
 * same in a trace and mean opposite things: that one is an identifier that was
 * never written, and this one is an identifier that is perfectly well formed
 * and belongs to a stack this device does not hold. One catch block in front of
 * both would be a catch block in front of two unrelated bugs.
 *
 * A `RuntimeException` rather than the `InvalidArgumentException` that type
 * extends, and the difference is the point: the argument here is not invalid.
 * A well-formed identifier for a stack this device was introduced to yesterday
 * and has since forgotten is the same argument it was, and what changed is the
 * state it is being asked about. `OutOfBoundsException` says exactly that and
 * is not on the analyser's list of parents this codebase extends; its own
 * parent is, and widening that list to fit one class would be weakening a rule
 * to accommodate the code rather than the other way round.
 */
final class StackIsNotConfigured extends RuntimeException
{
    public static function here(StackId $id): self
    {
        return new self(sprintf(
            'This device holds no stack identified as %s, so nothing can be attributed to it.',
            $id->stored(),
        ));
    }
}
