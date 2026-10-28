<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a word a screen draws means, in place, flattened for a template.
 *
 * Empty where the glossary does not carry the word, which is drawn then as it
 * came, with the way to ask the stack for it.
 */
final readonly class AGlossAsShown
{
    /**
     * @param string      $word  the glossary's word
     * @param string|null $short what it means, in a line, where the glossary carries the word
     * @param string|null $goes  where the longer gloss is, where there is one
     * @param string|null $asks  where the stack can be asked for the word, where the glossary does not carry it
     * @param bool        $waits whether the glossary waits for the next frame, so the word is drawn as it came for now
     */
    public function __construct(
        public string $word,
        public ?string $short = null,
        public ?string $goes = null,
        public ?string $asks = null,
        public bool $waits = false,
    ) {}
}
