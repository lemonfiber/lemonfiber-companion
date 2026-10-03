<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\TheItems;
use Modules\Operator\Internal\ViewModels\WhatAnItemSays;

/**
 * What one stack listed, as the items of news each kind is made of.
 *
 * The stack lists what it knows of each kind; the news module decides which of
 * them are new by the item alone. This is the join between the two: it names
 * each listed thing as an item, in the stack's order, and finds what the stack
 * said of an item again when a row is drawn or tapped.
 */
final readonly class TheNewsAsItems
{
    public function __construct(private TheNewsOfAStack $news) {}

    /** Whether the stack read this kind, so that its list is everything there is. */
    public function wasRead(KindOfNews $kind): bool
    {
        return match ($kind) {
            KindOfNews::Update => $this->news->releases()->wasRead(),
            KindOfNews::Request => $this->news->requests()->wasRead(),
            KindOfNews::Problem => $this->news->problems()->wasRead(),
        };
    }

    /** Every item of the kind the stack listed, newest first. */
    public function of(KindOfNews $kind): TheItems
    {
        $items = [];

        foreach ($this->said($kind) as $said) {
            $items[] = $said->item;
        }

        return TheItems::of($kind, ...$items);
    }

    /** What the stack said of the item of this kind named so, or nothing where it lists none. */
    public function find(KindOfNews $kind, string $named): ?WhatAnItemSays
    {
        foreach ($this->said($kind) as $said) {
            if ($said->item->named() === $named) {
                return $said;
            }
        }

        return null;
    }

    /** What the stack said of one item it listed. */
    public function saysOf(AnItem $item): ?WhatAnItemSays
    {
        return $this->find($item->kind(), $item->named());
    }

    /**
     * Each thing of the kind the stack listed, as an item with its words.
     *
     * @return list<WhatAnItemSays>
     */
    private function said(KindOfNews $kind): array
    {
        return match ($kind) {
            KindOfNews::Update => $this->releases(),
            KindOfNews::Request => $this->requests(),
            KindOfNews::Problem => $this->problems(),
        };
    }

    /** @return list<WhatAnItemSays> */
    private function releases(): array
    {
        $said = [];

        foreach ($this->news->releases() as $release) {
            $said[] = WhatAnItemSays::ofARelease(AnItem::anUpdate($release->version()), $release->version());
        }

        return $said;
    }

    /** @return list<WhatAnItemSays> */
    private function requests(): array
    {
        $said = [];

        foreach ($this->news->requests() as $request) {
            $said[] = WhatAnItemSays::ofARequest(AnItem::aRequest($request->id()->number()), $request->title());
        }

        return $said;
    }

    /** @return list<WhatAnItemSays> */
    private function problems(): array
    {
        $said = [];

        foreach ($this->news->problems() as $problem) {
            $said[] = WhatAnItemSays::ofAProblem(AnItem::aProblem($problem->check()->shown(), $problem->onset()), $problem->summary());
        }

        return $said;
    }
}
