<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the copy running on a stack is a backup or a restore.
 */
enum WhatIsCopied: string
{
    /** A copy of the stack being taken. */
    case Backup = 'backup';

    /** A copy being put back. */
    case Restore = 'restore';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.copy.running.what.%s', $this->value);
    }
}
