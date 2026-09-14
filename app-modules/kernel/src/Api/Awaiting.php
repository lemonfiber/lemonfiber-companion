<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What an operation with no clock on it is waiting for.
 *
 * A case rather than a sentence, so what something is waiting for is a value a
 * screen can be asked about — and so the words are built in one place. A phrase
 * carried from the stack would be a phrase this side had to parse back into the
 * fact it came from, and could not translate.
 */
enum Awaiting: string
{
    /** Everything still coming down has finished arriving. */
    case Downloads = 'downloads';

    /**
     * What a screen says this is.
     *
     * Built from the case rather than listed against it, so a case added
     * without a line fails the rule that reads both.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.awaiting.%s', $this->value);
    }
}
