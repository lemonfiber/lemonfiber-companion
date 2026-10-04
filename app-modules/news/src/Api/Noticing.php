<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\News\Internal\NewsOfAStack;
use Modules\News\Internal\TheNewestAsItems;
use Modules\News\Internal\TheNewestItem;

/**
 * What is new on a stack, against the newest of each kind the operator has seen there.
 *
 * One marker per stack and kind: the newest item seen. Anything newer is new,
 * where the kind is marked. Seeing an item raises the marker to it, so every
 * older item of the kind is seen with it and every newer one stays new.
 *
 * **The first sight of a kind records what is current as seen.** A stack read
 * for the first time, or a kind switched on again, has nothing to be newer
 * than, and marking everything it holds would be marking the past. So the
 * newest it holds becomes the marker, and only what arrives after is new.
 *
 * Sealed before it is kept and named by the stack's keyed hash, as everything
 * the phone keeps is, and forgotten with the stack.
 */
final readonly class Noticing implements ForgetsAStack, ForgetsEverythingKept
{
    public function __construct(private NewsOfAStack $news) {}

    /** The items newer than the newest of their kind seen on the stack, where their kind is marked. */
    public function whatIsNewIn(StackId $stack, TheItems $items): WhatIsNew
    {
        $kind = $items->kind();
        $kept = $this->news->of($stack);

        if (! $kept->isMarked($kind)) {
            return WhatIsNew::nothing();
        }

        if ($kept->hasSeen($kind)) {
            return $items->newerThan($kept->seen($kind));
        }

        $newest = TheNewestItem::in($items);

        if ($newest instanceof AnItem) {
            $this->news->keep($stack, $kept->seeing($newest));
        }

        return WhatIsNew::nothing();
    }

    /**
     * How much is new of each kind, by what the stack named as newest.
     *
     * Asked exactly as the list of what is new asks, kind by kind, so a tab,
     * the menu's count and the list never disagree: a kind switched off counts
     * nothing, the first sight of a kind records it as seen and counts nothing,
     * and a kind the stack could not read is not asked about at all. The count
     * is of what the stack named, which is up to ten of each kind. What it
     * named, and the count, are held for the screens that open after.
     */
    public function howMuchIsNew(StackId $stack, TheNewestNamed $newest): HowMuchIsNew
    {
        $counted = $this->counted($stack, $newest);
        $this->news->heard($stack, $newest, $counted);

        return $counted;
    }

    /**
     * How much is new on the stack by what it last named as newest in this process.
     *
     * So a screen opening on the stack draws what an earlier screen heard at
     * once. Counted again against what is kept where the operator has seen
     * anything, or switched a kind, since. Nothing where the stack has named
     * nothing yet.
     */
    public function howMuchWasLastNamed(StackId $stack): HowMuchIsNew
    {
        return $this->news->lastCounted($stack, fn(TheNewestNamed $newest): HowMuchIsNew => $this->counted($stack, $newest));
    }

    /** The operator has seen this item, and every older one of its kind with it; whether that was kept. */
    public function sawIt(StackId $stack, AnItem $item, TheItems $items): bool
    {
        $kept = $this->news->of($stack);

        if ($kept->hasSeen($item->kind()) && ! $items->isNewer($item, $kept->seen($item->kind()))) {
            return true;
        }

        return $this->news->keep($stack, $kept->seeing($item));
    }

    /** The operator has seen every item of the kind; whether that was kept. */
    public function sawThemAll(StackId $stack, TheItems $items): bool
    {
        $newest = TheNewestItem::in($items);

        return ! $newest instanceof AnItem || $this->sawIt($stack, $newest, $items);
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->news->forget($stack);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->news->keepsAnythingOf($stack);
    }

    /** Forget what is kept of every stack's news, and what each last named. */
    public function forgetEverything(): Forgotten
    {
        return $this->news->forgetEverything();
    }

    /** How much is new of each kind, kind by kind, as the list of what is new asks. */
    private function counted(StackId $stack, TheNewestNamed $newest): HowMuchIsNew
    {
        return HowMuchIsNew::holding(
            $newest->releases()->wasRead() ? $this->whatIsNewIn($stack, TheNewestAsItems::updates($newest)) : WhatIsNew::nothing(),
            $newest->requests()->wasRead() ? $this->whatIsNewIn($stack, TheNewestAsItems::requests($newest)) : WhatIsNew::nothing(),
            $newest->problems()->wasRead() ? $this->whatIsNewIn($stack, TheNewestAsItems::problems($newest)) : WhatIsNew::nothing(),
        );
    }
}
