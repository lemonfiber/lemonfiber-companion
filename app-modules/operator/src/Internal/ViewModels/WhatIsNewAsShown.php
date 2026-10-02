<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What's new as the screen draws it: the filters, the stacks it could not reach, and a section per kind. */
final readonly class WhatIsNewAsShown
{
    /**
     * @param list<AFilterAsShown>         $kinds     every kind to filter by, the one in force marked
     * @param list<AFilterAsShown>         $stacks    every stack to filter by, or none where one stack is paired
     * @param list<AnUnreachedStackAsShown> $unreached the stacks shown that could not be read
     * @param list<ANewsSectionAsShown>    $sections  a section per kind shown that has something new
     * @param bool                         $everyStackAsked whether every stack shown has been read or tried, so nothing is still to come
     */
    public function __construct(
        public array $kinds,
        public array $stacks,
        public array $unreached,
        public array $sections,
        public bool $everyStackAsked,
    ) {}

    /** Whether anything is new among what is shown, which is when *Mark all seen* is offered. */
    public function hasAnythingNew(): bool
    {
        return $this->sections !== [];
    }

    /** Whether the screen has nothing to show at all, and says so: every stack shown was asked, and none had anything. */
    public function isEmpty(): bool
    {
        return $this->everyStackAsked && $this->sections === [] && $this->unreached === [];
    }
}
