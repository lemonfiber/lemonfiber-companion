<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Instant;
use Modules\News\Api\MarkingAsNew;
use Modules\News\Api\Noticing;
use Modules\News\Internal\NewsOfAStack;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;

/**
 * What notices news for a screen a test builds by hand.
 *
 * Every screen that marks its tabs is handed one, so what it is made of is
 * written once: a phone that has kept nothing of what is new, or one whose
 * seal and store a test holds, so what it switches off is what is noticed.
 */
final readonly class NoticingWhatIsNew
{
    /** What notices news on a phone that has kept none of it. */
    public static function fromNothing(): Noticing
    {
        return new Noticing(self::newsOver(ASealInMemory::working(), NewsKeptInMemory::empty()));
    }

    /** What notices news over this seal and this store. */
    public static function over(ASealInMemory $seal, NewsKeptInMemory $kept): Noticing
    {
        return new Noticing(self::newsOver($seal, $kept));
    }

    /** Which kinds are marked as new, over this seal and this store. */
    public static function markingOver(ASealInMemory $seal, NewsKeptInMemory $kept): MarkingAsNew
    {
        return new MarkingAsNew(self::newsOver($seal, $kept));
    }

    private static function newsOver(ASealInMemory $seal, NewsKeptInMemory $kept): NewsOfAStack
    {
        return new NewsOfAStack($seal, $kept, FrozenClock::at(Instant::atEpochSeconds(0)));
    }
}
