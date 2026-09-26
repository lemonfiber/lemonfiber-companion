<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One line of a survey, as a catalogue key and the stack's words that fill it. */
final readonly class ALineOfTheSurvey
{
    /**
     * @param string                $said the catalogue key for the sentence
     * @param array<string, string> $with the stack's words, by the placeholder each fills
     */
    public function __construct(
        public string $said,
        public array $with,
    ) {}
}
