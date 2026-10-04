<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\ForgetsAStack;
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
final readonly class Noticing implements ForgetsAStack
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
     * Which tabs hold something new, and how many, by what the stack named as newest.
     *
     * Asked exactly as the list of what is new asks, kind by kind, so a tab
     * and the list never disagree: a kind switched off marks nothing, the
     * first sight of a kind records it as seen and marks nothing, and a kind
     * the stack could not read is not asked about at all.
     */
    public function whatTheTabsHold(StackId $stack, TheNewestNamed $newest): TheTabsMarked
    {
        return TheTabsMarked::holding(
            $newest->releases()->wasRead() ? $this->whatIsNewIn($stack, TheNewestAsItems::updates($newest)) : WhatIsNew::nothing(),
            $newest->problems()->wasRead() ? $this->whatIsNewIn($stack, TheNewestAsItems::problems($newest)) : WhatIsNew::nothing(),
        );
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
}
