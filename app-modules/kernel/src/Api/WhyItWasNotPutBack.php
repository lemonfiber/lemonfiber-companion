<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Why a stack would not put a run back, in its own words.
 *
 * The problem the stack stopped on: its one-sentence summary, what that means,
 * and what it named in `detail`. A stamp that names no run, a stamp naming
 * more than one, and a change that cannot be reversed each arrive this way,
 * and none of them is changed by asking again. The summary is the refusal;
 * the meaning and the named part may be blank where the stack gave none.
 *
 * What was named is carried as {@see WhatTheRefusalNamed}, redacted from every
 * reader but the screen, because a fault in putting a change back names the
 * file on the machine it could not change.
 */
final readonly class WhyItWasNotPutBack
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
            throw UndoSaysNothing::about('summary');
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
