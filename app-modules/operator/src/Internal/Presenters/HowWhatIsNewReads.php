<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;

use Modules\Design\Api\ATap;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\Noticing;
use Modules\Operator\Internal\TheNewsAsItems;
use Modules\Operator\Internal\ViewModels\AFilterAsShown;
use Modules\Operator\Internal\ViewModels\ANewsRowAsShown;
use Modules\Operator\Internal\ViewModels\ANewsSectionAsShown;
use Modules\Operator\Internal\ViewModels\AnUnreachedStackAsShown;
use Modules\Operator\Internal\ViewModels\WhatAnItemSays;
use Modules\Operator\Internal\ViewModels\WhatEachStackListed;
use Modules\Operator\Internal\ViewModels\WhatIsNewAsShown;

use function sprintf;

/**
 * What's new, folded from what each stack listed into what the screen draws.
 *
 * **A section per kind, in the order the kinds are declared**: updates, then
 * requests, then problems. The kinds have no order between them — a release has
 * no time, a request a number and a problem an onset — so each is newest first
 * within its own section, stack by stack in the operator's order.
 *
 * **What is new is the news module's to decide**, by {@see Noticing}, which also
 * records a kind's first sight as seen. A kind the stack could not read is not
 * asked about at all: an unread list is not everything there is.
 *
 * **Stacks it could not reach come first**, each with when it was last read
 * where the phone knows, so nothing missing from below reads as nothing new.
 */
final readonly class HowWhatIsNewReads
{
    public function __construct(private Noticing $noticing) {}

    /**
     * What the screen draws, narrowed by kind and stack.
     *
     * @param KindOfNews|null $kind  the kind shown, or null for every kind
     * @param StackId|null    $stack the stack shown, or null for every stack
     */
    public function shown(Configured $stacks, WhatEachStackListed $listed, ?KindOfNews $kind, ?StackId $stack, Instant $now): WhatIsNewAsShown
    {
        $shown = $this->stacksShown($stacks, $stack);
        $unreached = [];
        $everyStackAsked = true;

        foreach ($shown as $one) {
            $lastKnown = $listed->lastKnownOf($one->id());
            $everyStackAsked = $everyStackAsked && $listed->hasAsked($one->id());

            if ($lastKnown instanceof Showing) {
                $unreached[] = $this->unreached($one, $lastKnown, $now);
            }
        }

        $sections = [];

        foreach (self::kindsShown($kind) as $one) {
            $rows = $this->rows($shown, $listed, $one);

            if ($rows !== []) {
                $sections[] = new ANewsSectionAsShown($one->saidOnTheScreen(), $rows);
            }
        }

        return new WhatIsNewAsShown($this->kinds($kind), $this->stacks($stacks, $stack), $unreached, $sections, everyStackAsked: $everyStackAsked);
    }

    /**
     * The kinds a filter shows.
     *
     * @return list<KindOfNews>
     */
    public static function kindsShown(?KindOfNews $kind): array
    {
        return $kind instanceof KindOfNews ? [$kind] : KindOfNews::cases();
    }

    /**
     * The stacks a filter shows, in the operator's order.
     *
     * @return list<Stack>
     */
    public function stacksShown(Configured $stacks, ?StackId $stack): array
    {
        $shown = [];

        foreach ($stacks as $one) {
            if (! $stack instanceof StackId || $one->id()->is($stack)) {
                $shown[] = $one;
            }
        }

        return $shown;
    }

    /**
     * Every new item of one kind on the stacks shown, stack by stack, each newest first.
     *
     * @param list<Stack> $stacks
     *
     * @return list<ANewsRowAsShown>
     */
    private function rows(array $stacks, WhatEachStackListed $listed, KindOfNews $kind): array
    {
        $rows = [];

        foreach ($stacks as $stack) {
            $news = $listed->of($stack->id());

            if (!$news instanceof TheNewsAsItems || ! $news->wasRead($kind)) {
                continue;
            }

            foreach ($this->noticing->whatIsNewIn($stack->id(), $news->of($kind)) as $item) {
                $says = $news->saysOf($item);

                if ($says instanceof WhatAnItemSays) {
                    $rows[] = new ANewsRowAsShown(
                        $says,
                        sprintf('news.kind_one.%s', $kind->value),
                        $stack->name()->shown(),
                        ATap::calling('open', $stack->id()->stored(), $kind->value, $item->named())->written(),
                    );
                }
            }
        }

        return $rows;
    }

    /** A stack that could not be read, with when the phone last heard from it. */
    private function unreached(Stack $stack, Showing $lastKnown, Instant $now): AnUnreachedStackAsShown
    {
        $ago = $lastKnown->either(
            waiting: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
            holding: static fn(Reading $reading): AgoAsShown => $reading->either(
                live: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
                retained: static fn(object $value, Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
            ),
        );

        return new AnUnreachedStackAsShown($stack->name()->shown(), $ago->said, $ago->count);
    }

    /**
     * Every kind to filter by, everything first.
     *
     * @return list<AFilterAsShown>
     */
    private function kinds(?KindOfNews $kind): array
    {
        $filters = [new AFilterAsShown(said: 'news.all_kinds', name: null, tap: ATap::calling('showEveryKind')->written(), chosen: ! $kind instanceof KindOfNews)];

        foreach (KindOfNews::cases() as $one) {
            $filters[] = new AFilterAsShown(said: $one->saidOnTheScreen(), name: null, tap: ATap::calling('showKind', $one->value)->written(), chosen: $one === $kind);
        }

        return $filters;
    }

    /**
     * Every stack to filter by, every stack first, or none where only one is paired.
     *
     * @return list<AFilterAsShown>
     */
    private function stacks(Configured $stacks, ?StackId $stack): array
    {
        $filters = [new AFilterAsShown(said: 'news.all_stacks', name: null, tap: ATap::calling('showEveryStack')->written(), chosen: ! $stack instanceof StackId)];

        foreach ($stacks as $one) {
            $filters[] = new AFilterAsShown(said: null, name: $one->name()->shown(), tap: ATap::calling('showStack', $one->id()->stored())->written(), chosen: $stack instanceof StackId && $one->id()->is($stack));
        }

        // Every stack first and then each one, so one paired stack makes two
        // filters that choose the same thing, and none is offered.
        return count($filters) > 2 ? $filters : [];
    }
}
