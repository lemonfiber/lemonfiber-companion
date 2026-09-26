<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A setup lemonfiber does not manage, as a copy of it names it.
 *
 * The Compose project the copy was taken from and the host paths it read.
 * A copy of somebody else's directories is a different undertaking from a
 * copy of the stack's own, and these two are what say whose.
 */
final readonly class AnExistingSetup
{
    private function __construct(
        private string $project,
        private WhatACopyHolds $trees,
    ) {}

    /** The project by the name Compose gave it, and the trees read from it. */
    public static function of(string $project, WhatACopyHolds $trees): self
    {
        if (trim($project) === '') {
            throw KeepingSaysNothing::about('project');
        }

        return new self($project, $trees);
    }

    /** What the project is called. */
    public function project(): string
    {
        return $this->project;
    }

    /** Where each tree the copy holds was read from, in the order they were read. */
    public function trees(): WhatACopyHolds
    {
        return $this->trees;
    }
}
