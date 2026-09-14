<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Category;
use Modules\Operator\Internal\ViewModels\WhichFamilyToRead;

/**
 * One family of checks, as the control an operator taps to narrow a report.
 *
 * **The count is on the control, not behind it.** An operator deciding which
 * family to open is deciding where the trouble is, and a row of names with no
 * numbers makes them open each one to find out. It is also what makes the
 * control honest about itself: the caller passes only families that hold
 * something, so a family with nothing in it is never drawn.
 *
 * **The key is taken off the case rather than spelled.** The word is the
 * catalogue's, so a family added to the contract cannot arrive here with a
 * sentence written in this file that no translator can reach (`L1`).
 */
final readonly class HowAFamilyReads
{
    public function of(Category $family, int $howMany, bool $isOpen): WhichFamilyToRead
    {
        return new WhichFamilyToRead($family->saidOnTheScreen(), $family->value, $howMany, $isOpen);
    }
}
