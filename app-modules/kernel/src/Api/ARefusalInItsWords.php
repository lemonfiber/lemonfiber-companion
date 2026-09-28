<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Why a stack would not do what it was asked, in its own words.
 *
 * The problem it answered with: its one-sentence summary, what that means, and
 * what it named in `detail`. That is an answer about the work, not a stack
 * that could not be reached, and asking the same again is answered the same
 * way. The summary is the refusal; the meaning and the named part are blank
 * where the stack gave none.
 *
 * What was named is carried as {@see WhatTheRefusalNamed}, redacted from every
 * reader but the screen that asked, because it can name a file on the machine.
 */
final readonly class ARefusalInItsWords
{
    private function __construct(
        private string $summary,
        private string $meaning,
        private WhatTheRefusalNamed $named,
    ) {}

    /** What the stack said, refused where the summary is blank. */
    public static function said(string $summary, string $meaning, WhatTheRefusalNamed $named): self
    {
        $summary = trim($summary);

        if ($summary === '') {
            throw RefusalSaysNothing::about('summary');
        }

        return new self($summary, trim($meaning), $named);
    }

    /** The stack's one sentence. */
    public function summary(): string
    {
        return $this->summary;
    }

    /** What the sentence means, or blank where the stack said no more. */
    public function meaning(): string
    {
        return $this->meaning;
    }

    /** What the stack named, for the screen that asked. */
    public function named(): WhatTheRefusalNamed
    {
        return $this->named;
    }
}
