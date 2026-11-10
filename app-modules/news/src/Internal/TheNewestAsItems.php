<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Modules\Kernel\Api\TheNewestNamed;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\TheItems;

/**
 * What a stack named as newest, as the items of each kind.
 *
 * The event names an item by exactly what an item is named and ordered by: a
 * release by its version, a request by its place in the household list, and a problem by its check
 * and onset.
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

    /** The household's newest requests the stack named, as requests, newest asked first. */
    public static function requests(TheNewestNamed $newest): TheItems
    {
        $items = [];

        foreach ($newest->requests() as $request) {
            $items[] = AnItem::aRequest($request);
        }

        return TheItems::of(KindOfNews::Request, ...$items);
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
