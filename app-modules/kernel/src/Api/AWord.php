<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_stripos;
use function trim;

/**
 * One of lemonfiber's words, as its glossary explains it.
 *
 * The short gloss is what stands in place of the word; the longer one is there
 * for whoever asks and is empty where the glossary has none. What else the
 * thing is called is part of what it means, since it is what somebody arriving
 * from another tool knows it as.
 */
final readonly class AWord
{
    private function __construct(
        private string $word,
        private string $short,
        private string $deep,
        private WhatElseItIsCalled $alsoCalled,
        private WhatElseItIsCalled $forms,
    ) {}

    /**
     * The glossary's entry for one word.
     *
     * The word and its short gloss are required. The longer gloss may be
     * empty; a blank one, or a blank other name, is refused.
     */
    public static function explained(string $word, string $short, string $deep, string ...$alsoCalled): self
    {
        foreach (['word' => $word, 'short' => $short] as $field => $said) {
            if (trim($said) === '') {
                throw WordsSayNothing::about($field);
            }
        }

        if ($deep !== '' && trim($deep) === '') {
            throw WordsSayNothing::about('deep');
        }

        return new self($word, $short, $deep, WhatElseItIsCalled::of(...$alsoCalled), WhatElseItIsCalled::formsOf());
    }

    /**
     * The same word, found as well by the forms lemonfiber writes it in.
     *
     * A stage the stack sends as `grabbed` is explained by the entry for
     * `grab`. The forms are not drawn: they are the word, not another name
     * for it.
     */
    public function writtenAs(WhatElseItIsCalled $forms): self
    {
        return new self($this->word, $this->short, $this->deep, $this->alsoCalled, $forms);
    }

    /** The word itself. */
    public function word(): string
    {
        return $this->word;
    }

    /** What it means, in a line. */
    public function short(): string
    {
        return $this->short;
    }

    /** What it means at length, or empty where the glossary has no more to say. */
    public function deep(): string
    {
        return $this->deep;
    }

    /** What else it is called. */
    public function alsoCalled(): WhatElseItIsCalled
    {
        return $this->alsoCalled;
    }

    /** Whether this is the word a screen drew, by its own name or another it goes by. */
    public function explains(AWordInUse $word): bool
    {
        return $word->is(AWordInUse::named($this->word)) || $this->alsoCalled->include($word) || $this->forms->include($word);
    }

    /**
     * Whether this word, or anything else it is called, holds what somebody is looking for.
     *
     * Case-insensitive, for {@see Said::holds()}'s reason. The glosses are not
     * searched: a search for a word is a search for its names.
     */
    public function answers(LookingFor $looking): bool
    {
        return mb_stripos($this->word, $looking->typed()) !== false || $this->alsoCalled->answer($looking) || $this->forms->answer($looking);
    }
}
