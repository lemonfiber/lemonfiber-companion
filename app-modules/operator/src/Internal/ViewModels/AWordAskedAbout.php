<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking the stack for one word came to, where it did not explain it.
 *
 * A word it explained joins the glossary the screen holds and is drawn as
 * every other word is, so it needs nothing here.
 */
final readonly class AWordAskedAbout
{
    /**
     * @param string $word        the word as it was asked for
     * @param bool   $unexplained whether the stack has no entry for it either
     * @param string $met         what stood in the way of asking, as a key, or empty
     */
    public function __construct(
        public string $word,
        public bool $unexplained,
        public string $met,
    ) {}
}
