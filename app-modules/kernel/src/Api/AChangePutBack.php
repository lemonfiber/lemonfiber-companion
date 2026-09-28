<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One change putting a run back reversed: what it was against, and what going back did.
 *
 * On a rehearsal, what going back would do. Which of the two it is belongs to
 * the report this sits in, never to the row.
 */
final readonly class AChangePutBack
{
    private function __construct(private string $target, private WhatGoingBackDoes $does) {}

    /** One reversal as the stack reported it; a blank target is refused. */
    public static function against(string $target, WhatGoingBackDoes $does): self
    {
        $said = trim($target);

        if ($said === '') {
            throw UndoSaysNothing::about('target');
        }

        return new self($said, $does);
    }

    /** The service or file it was put back against. */
    public function target(): string
    {
        return $this->target;
    }

    /** What going back did to it. */
    public function does(): WhatGoingBackDoes
    {
        return $this->does;
    }
}
