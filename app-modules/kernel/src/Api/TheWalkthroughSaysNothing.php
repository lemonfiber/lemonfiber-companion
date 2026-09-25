<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A walkthrough arrived with a sentence it is required to say left blank.
 *
 * A developer reads it, so it is `sprintf` and never translated.
 */
final class TheWalkthroughSaysNothing extends InvalidArgumentException
{
    /** The field that was blank, as the wire names it. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A walkthrough arrived with its `%s` blank, and a narration that leaves a line out is not the one the operator watched.',
            $field,
        ));
    }
}
