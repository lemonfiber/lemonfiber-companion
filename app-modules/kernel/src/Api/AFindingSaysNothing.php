<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Something a change would come to was named with a part that says nothing.
 */
final class AFindingSaysNothing extends InvalidArgumentException
{
    public static function about(string $part): self
    {
        return new self(sprintf('What a change would come to has a `%s` that says nothing, and a blank line there reads as nothing being disturbed.', $part));
    }

    public static function progress(int $said): self
    {
        return new self(sprintf('A download a change would interrupt is %d%% through, which is not between 0 and 100.', $said));
    }
}
