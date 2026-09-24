<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a word a screen draws means, in place, flattened for a template.
 *
 * Empty where the glossary does not carry the word, which is drawn then as it
 * came.
 */
final readonly class AGlossAsShown
{
    /**
     * @param string $word  the glossary's word
     * @param string $short what it means, in a line, or empty
     * @param string $goes  where the longer gloss is, or empty where there is none
     */
    public function __construct(
        public string $word,
        public string $short,
        public string $goes,
    ) {}

    /** Whether the glossary explains the word. */
    public function isExplained(): bool
    {
        return $this->short !== '';
    }
}
