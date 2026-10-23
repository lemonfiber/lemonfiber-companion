<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a quality choice may be made from, each in the stack's words: its presets, the formats music may take, and the kinds of media a choice may be set apart for.
 *
 * The stack's own vocabulary, carried with every answer about quality so a
 * screen offers what the stack takes rather than a copy of it kept here.
 * Each list is in the stack's order.
 */
final readonly class WhatAChoiceMayBeMadeFrom
{
    private function __construct(
        private WordsToChooseFrom $presets,
        private WordsToChooseFrom $formats,
        private WordsToChooseFrom $kinds,
    ) {}

    /** Every resolution preset, every format music may take, and every kind of media a choice may be made for apart from everything. */
    public static function of(WordsToChooseFrom $presets, WordsToChooseFrom $formats, WordsToChooseFrom $kinds): self
    {
        return new self($presets, $formats, $kinds);
    }

    /** Every resolution preset, least demanding first. */
    public function presets(): WordsToChooseFrom
    {
        return $this->presets;
    }

    /** Every format music may take, least demanding first. */
    public function formats(): WordsToChooseFrom
    {
        return $this->formats;
    }

    /** Every kind of media a choice may be set apart for. */
    public function kinds(): WordsToChooseFrom
    {
        return $this->kinds;
    }
}
