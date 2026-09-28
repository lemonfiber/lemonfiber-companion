<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What carrying an operator's own records across came to, or would come to.
 *
 * **What could not be carried is its own part, not a footnote.** It sits
 * beside what was carried as an equal, with the reason for each, because an
 * operator who believes their quality profiles came over and finds in three
 * weeks that they did not was told something true and useless.
 *
 * A project of `''` is the stack naming none.
 */
final readonly class TheImport
{
    private function __construct(
        private string $project,
        private TheRecords $carried,
        private TheRecords $wouldCarry,
        private WhatIsUnsupported $notCarried,
    ) {}

    /** What the stack said, as it said it. */
    public static function of(string $project, TheRecords $carried, TheRecords $wouldCarry, WhatIsUnsupported $notCarried): self
    {
        return new self($project, $carried, $wouldCarry, $notCarried);
    }

    /** The project the records were read from, or `''` where the stack named none. */
    public function project(): string
    {
        return $this->project;
    }

    /** What was carried across. */
    public function carried(): TheRecords
    {
        return $this->carried;
    }

    /** What would be, where nothing has been yet. */
    public function wouldCarry(): TheRecords
    {
        return $this->wouldCarry;
    }

    /** What could not be carried, each with why. */
    public function notCarried(): WhatIsUnsupported
    {
        return $this->notCarried;
    }
}
