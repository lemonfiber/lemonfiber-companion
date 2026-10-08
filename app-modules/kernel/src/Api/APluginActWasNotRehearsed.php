<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * Installing, updating or removing a plugin was agreed to against something that was not a reading of that act.
 *
 * A developer reads it: the screen offers the yes only beneath a reading of
 * the act it agrees to, so reaching this is a caller that skipped one, or
 * quoted one act's reading for another.
 */
final class APluginActWasNotRehearsed extends LogicException
{
    /** The answer agreed against carried no reading of this act. */
    public static function before(ExtendingIt $act): self
    {
        return new self(sprintf('Agreed to %s a plugin against an answer that was not a reading of doing so, which is not an account anybody was shown before agreeing.', $act->value));
    }
}
