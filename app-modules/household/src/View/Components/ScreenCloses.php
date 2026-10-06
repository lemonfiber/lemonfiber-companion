<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Household\Internal\WhereTheHouseIs;
use Modules\Wayfinding\Api\TheHouseholdsTabs;

use function view;

/**
 * The bar a member's screen closes with: Home, Search, Requests and Profile.
 *
 * Every member screen emits it, and the screen says whether it is shown: a
 * tab's screen shows it, and a screen opened over a tab hides it. The tab the
 * screen is under is the only one marked, to sight and to a screen reader.
 */
final class ScreenCloses extends Component
{
    /**
     * @param WhereTheHouseIs         $goes where this house's screens are
     * @param TheHouseholdsTabs|null  $here the tab the screen is under, or none
     */
    public function __construct(
        public readonly WhereTheHouseIs $goes,
        public readonly ?TheHouseholdsTabs $here,
    ) {}

    public function render(): View
    {
        return view('household::components.screen-closes');
    }
}
