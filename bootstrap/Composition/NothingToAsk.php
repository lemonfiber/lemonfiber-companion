<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use LogicException;

use function sprintf;

/**
 * A set of keepers asked as one was built with none to ask, so asking it would let go of nothing.
 *
 * A `LogicException` because only the wiring produces it: the composition root
 * hands each set its keepers under a tag, and a set with none is a tag that is
 * missing or empty.
 */
final class NothingToAsk extends LogicException
{
    public static function in(string $set): self
    {
        return new self(sprintf('%s was built with no keeper to ask, so it would let go of nothing: check its tag in the composition root.', $set));
    }
}
