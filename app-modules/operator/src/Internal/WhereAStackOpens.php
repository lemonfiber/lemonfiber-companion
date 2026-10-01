<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Stack;

/**
 * Where the app opens, asked once a run, and where a stack opens when it is chosen.
 *
 * The first time Your stacks is built in a run is the opening, and it goes on
 * to where the operator left off. Every later time is somebody coming back to
 * the list, and stays there. One per run, so the container holds one.
 */
final class WhereAStackOpens
{
    private bool $landed = false;

    public function __construct(private readonly TheWayAround $around) {}

    /** Where the opening goes, the first time it is asked in a run; nowhere after that. */
    public function theOpening(): string
    {
        if ($this->landed) {
            return '';
        }

        $this->landed = true;

        return $this->around->whereTheOpeningLands();
    }

    /** The stack, on the tab the operator last used there. */
    public function onItsLastTab(Stack $stack): string
    {
        return $this->around->onItsLastTab($stack);
    }
}
