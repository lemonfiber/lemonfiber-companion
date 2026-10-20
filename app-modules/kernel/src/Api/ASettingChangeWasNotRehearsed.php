<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * Changing a setting was agreed to against something that was not a rehearsal of that change.
 */
final class ASettingChangeWasNotRehearsed extends LogicException
{
    /** The answer agreed against was no rehearsal of this change. */
    public static function before(ChangingASetting $how): self
    {
        return new self(sprintf('Agreed to %s against an answer that was not a rehearsal of it, which is not a change anybody was shown before agreeing.', $how->asked()));
    }
}
