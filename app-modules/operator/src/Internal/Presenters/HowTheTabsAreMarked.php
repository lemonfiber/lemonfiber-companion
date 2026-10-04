<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\WhichTab;
use Modules\News\Api\TheTabsMarked;
use Modules\Operator\Internal\ViewModels\AMarkAsShown;
use Modules\Operator\Internal\ViewModels\TheTabsAsMarked;

use function sprintf;

/**
 * Which tabs hold something new, as the marks the bar draws.
 *
 * Data in, view model out. What is new is the news module's to decide; this
 * reads it off for the two tabs the bar marks, and writes each count out as
 * the text a badge draws.
 */
final readonly class HowTheTabsAreMarked
{
    public function of(TheTabsMarked $marked): TheTabsAsMarked
    {
        return new TheTabsAsMarked(
            health: $this->mark($marked->howManyOn(WhichTab::Health)),
            updates: $this->mark($marked->howManyOn(WhichTab::Updates)),
        );
    }

    /** One tab's mark: the count, and the count as text, or none where nothing is new. */
    private function mark(int $count): AMarkAsShown
    {
        return $count > 0 ? new AMarkAsShown($count, sprintf('%d', $count)) : new AMarkAsShown();
    }
}
