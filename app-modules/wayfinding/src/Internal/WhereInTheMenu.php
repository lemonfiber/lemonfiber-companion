<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use function array_filter;
use function array_values;
use function sprintf;

/**
 * The five groups of the side menu, in the order it draws them.
 */
enum WhereInTheMenu: string
{
    case Household = 'household';
    case Access = 'access';
    case Machine = 'machine';
    case Settings = 'settings';
    case Help = 'help';

    /** The catalogue key of the group's heading. */
    public function said(): string
    {
        return sprintf('navigation.groups.%s', $this->value);
    }

    /**
     * The items drawn under this group, in the menu's order.
     *
     * @return list<TheMenu>
     */
    public function holds(): array
    {
        return array_values(array_filter(
            TheMenu::cases(),
            fn(TheMenu $item): bool => $item->group() === $this,
        ));
    }
}
