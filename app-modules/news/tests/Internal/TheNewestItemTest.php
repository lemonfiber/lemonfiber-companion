<?php

declare(strict_types=1);

namespace Modules\News\Tests\Internal;

use function expect;
use function it;

use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\TheItems;
use Modules\News\Internal\TheNewestItem;

it('is the first of a list kept newest first, and nothing of an empty one', function (): void {
    expect(TheNewestItem::in(TheItems::of(KindOfNews::Request, AnItem::aRequest(9), AnItem::aRequest(4)))?->named())->toBe('9')
        ->and(TheNewestItem::in(TheItems::of(KindOfNews::Request)))->toBeNull();
});
