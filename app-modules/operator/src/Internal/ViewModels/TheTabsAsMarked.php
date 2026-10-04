<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The mark each marked tab carries, as the bar draws them.
 *
 * Only Health and Updates are ever marked: a problem is read on the one and an
 * update on the other. A tab with nothing new carries none.
 */
final readonly class TheTabsAsMarked
{
    /**
     * @param AMarkAsShown $health  the mark Health carries for new problems
     * @param AMarkAsShown $updates the mark Updates carries for new updates
     */
    public function __construct(
        public AMarkAsShown $health = new AMarkAsShown(),
        public AMarkAsShown $updates = new AMarkAsShown(),
    ) {}
}
