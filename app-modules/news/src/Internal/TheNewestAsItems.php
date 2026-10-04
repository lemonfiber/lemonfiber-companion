<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Modules\Kernel\Api\TheNewestNamed;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\TheItems;

/**
 * What a stack named as newest, as the items of the kinds a tab is marked for.
 *
 * The event names an item by exactly what an item is named and ordered by: a
 * release by its version and a problem by its check and onset. Requests mark
 * no tab, so they are not made items here.
 */
final readonly class TheNewestAsItems
{
    /** The newest releases the stack named, as updates, newest first. */
    public static function updates(TheNewestNamed $newest): TheItems
    {
        $items = [];

        foreach ($newest->releases() as $release) {
            $items[] = AnItem::anUpdate($release->version());
        }

        return TheItems::of(KindOfNews::Update, ...$items);
    }

    /** The checks the stack named as most recently found wrong, as problems, newest first. */
    public static function problems(TheNewestNamed $newest): TheItems
    {
        $items = [];

        foreach ($newest->problems() as $problem) {
            $items[] = AnItem::aProblem($problem->check()->shown(), $problem->onset());
        }

        return TheItems::of(KindOfNews::Problem, ...$items);
    }
}
