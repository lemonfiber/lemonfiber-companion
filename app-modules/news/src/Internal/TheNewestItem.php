<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Modules\News\Api\AnItem;
use Modules\News\Api\TheItems;

/** The newest of a list of items, where it holds any. */
final readonly class TheNewestItem
{
    /** The first, since the list is newest first; nothing where it is empty. */
    public static function in(TheItems $items): ?AnItem
    {
        foreach ($items as $item) {
            return $item;
        }

        return null;
    }
}
