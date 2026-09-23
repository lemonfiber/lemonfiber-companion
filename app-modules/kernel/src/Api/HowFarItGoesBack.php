<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How far one change the stack made could be put back.
 *
 * Three, and the contract names all three in the sentence that describes the
 * field rather than in a closed type: `whole`, `partial`, `none`. They are
 * read as a closed set anyway, for {@see HowItIsHosted}'s reason — a word this
 * app has no case for is refused at the reading rather than drawn as the
 * nearest one, and the nearest one to an unknown word about undoing something
 * is always a guess about whether it can be undone.
 *
 * **`None` is a case, not an absence.** A change which cannot be reversed
 * says so rather than omitting the field, and a nullable reversal is exactly
 * the omission: a row with nothing where *how to put it
 * back* belongs reads as a row nobody thought about, and an operator deciding
 * whether to try is owed the difference.
 */
enum HowFarItGoesBack: string
{
    /** Everything it did can be put back. */
    case Whole = 'whole';

    /** Some of it can, and some of it could not. */
    case Partial = 'partial';

    /** None of it can. */
    case None = 'none';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.reversal.%s', $this->value);
    }
}
