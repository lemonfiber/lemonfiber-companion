<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A change asked to give values and put settings back in one act, which the stack takes as two.
 */
final class ASettingChangeIsOneAct extends InvalidArgumentException
{
    /** A value was added to a change that puts settings back. */
    public static function alreadyPuttingBack(): self
    {
        return new self('A change that puts settings back to their defaults cannot give a setting a value too; that is a change of its own.');
    }

    /** A setting was put back in a change that gives values. */
    public static function alreadyGivingValues(): self
    {
        return new self('A change that gives settings values cannot put a setting back to its default too; that is a change of its own.');
    }
}
