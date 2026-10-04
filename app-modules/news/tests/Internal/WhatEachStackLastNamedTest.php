<?php

declare(strict_types=1);

namespace Modules\News\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\News\Api\AnItem;
use Modules\News\Api\HowMuchIsNew;
use Modules\News\Api\WhatIsNew;
use Modules\News\Internal\WhatEachStackLastNamed;

use function str_repeat;

/** The stack whose newest is held. */
function theStackWhoseNewestIsHeld(): StackId
{
    return StackId::of(Nonce::of(str_repeat('h', Nonce::SHORTEST)));
}

/** What the stack names as newest: one release. */
function oneReleaseNamed(): TheNewestNamed
{
    return new TheNewestNamed(WhatTheStackListed::these(AReleaseNamed::versioned('2.4.0')), WhatTheStackListed::these(), WhatTheStackListed::these());
}

/** One new update. */
function oneNewUpdate(): HowMuchIsNew
{
    return HowMuchIsNew::holding(WhatIsNew::these(AnItem::anUpdate('2.4.0')), WhatIsNew::nothing(), WhatIsNew::nothing());
}

it('answers nothing for a stack that has named nothing, and never counts it', function (): void {
    $counts = 0;
    $held = new WhatEachStackLastNamed()->lastCounted(theStackWhoseNewestIsHeld(), static function () use (&$counts): HowMuchIsNew {
        $counts++;

        return oneNewUpdate();
    });

    expect($held->howManyInAll())->toBe(0)
        ->and($counts)->toBe(0);
});

it('answers what was counted as the stack named it, without counting again until what is kept changes', function (): void {
    $lastNamed = new WhatEachStackLastNamed();
    $lastNamed->named(theStackWhoseNewestIsHeld(), oneReleaseNamed(), oneNewUpdate());
    $named = [];
    $counting = static function (TheNewestNamed $newest) use (&$named): HowMuchIsNew {
        $named[] = $newest;

        return HowMuchIsNew::none();
    };

    $before = $lastNamed->lastCounted(theStackWhoseNewestIsHeld(), $counting)->howManyInAll();
    $lastNamed->changed(theStackWhoseNewestIsHeld());
    $after = $lastNamed->lastCounted(theStackWhoseNewestIsHeld(), $counting)->howManyInAll();
    $lastNamed->lastCounted(theStackWhoseNewestIsHeld(), $counting);

    expect($before)->toBe(1)
        ->and($after)->toBe(0)
        ->and($named)->toBe([$named[0]])
        ->and($named[0]->releases()->wasRead())->toBeTrue();
});

it('forgets a stack, and every stack', function (): void {
    $lastNamed = new WhatEachStackLastNamed();
    $other = StackId::of(Nonce::of(str_repeat('i', Nonce::SHORTEST)));
    $lastNamed->named(theStackWhoseNewestIsHeld(), oneReleaseNamed(), oneNewUpdate());
    $lastNamed->named($other, oneReleaseNamed(), oneNewUpdate());
    $recount = static fn(): HowMuchIsNew => oneNewUpdate();

    $lastNamed->forget(theStackWhoseNewestIsHeld());
    $forgotten = $lastNamed->lastCounted(theStackWhoseNewestIsHeld(), $recount)->howManyInAll();
    $still = $lastNamed->lastCounted($other, $recount)->howManyInAll();
    $lastNamed->forgetEverything();

    expect($forgotten)->toBe(0)
        ->and($still)->toBe(1)
        ->and($lastNamed->lastCounted($other, $recount)->howManyInAll())->toBe(0);
});
