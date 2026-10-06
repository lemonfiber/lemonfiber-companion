<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\TakesTheThemeItOpensOver;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingNews;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\Noticing;
use Modules\Operator\Internal\HasAWayBack;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\Presenters\HowWhatIsNewReads;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\TheNewsAsItems;
use Modules\Operator\Internal\ViewModels\WhatAnItemSays;
use Modules\Operator\Internal\ViewModels\WhatEachStackListed;
use Modules\Operator\Internal\ViewModels\WhatIsNewAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What is new on every stack this phone holds, a section per kind.
 *
 * **Each stack shown is read once, one stack a frame**, in the operator's
 * order, and what it listed is held: a filter, a tap and *Mark all seen* all act
 * on the list the operator is looking at. A frame that has read a stack asks for
 * the next frame at once while another stack shown is still to be read, so a
 * stack slow to answer holds up only what it lists. It reads again once a
 * minute while open, because what is new changes on its own.
 *
 * **What is new is the news module's**: newer than the newest of its kind the
 * operator has seen on that stack, where that stack marks the kind. Opening an
 * item marks it seen, and every older one of its kind with it; *Mark all seen*
 * marks everything shown, and only what is shown.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs and asks for is the household's business.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatIsNewOnEveryStack extends NativeComponent implements TakesTheThemeItOpensOver
{
    use HasAWayBack;
    use LetsGoOfARefusedSession;
    use LooksAgainWhileOpen;
    use ReadsAStackOnceAFrame;

    /** The kind shown, by its value, or every kind. */
    public string $kind = HowWhatIsNewReads::EVERYTHING;

    /** The stack shown, by its stored identifier, or every stack. */
    public string $stackShown = HowWhatIsNewReads::EVERYTHING;

    /**
     * What each stack listed when it was last read, held between frames.
     *
     * `public` so the screen holds it, for {@see HowThisStackIs::$answered}'s reason.
     */
    public ?WhatEachStackListed $listed = null;

    /** What this frame draws, worked out once however often the template asks. */
    private ?WhatIsNewAsShown $shown = null;

    public function __construct(
        private readonly Stacks $stacks,
        private readonly SecureStorage $storage,
        private readonly ReadingNews $reading,
        private readonly Noticing $noticing,
        private readonly Standings $standings,
        private readonly Clock $clock,
    ) {}

    /** Open on the stack a stack's menu opened this from, which the operator can widen to every stack. */
    public function mount(): void
    {
        $shows = $this->data(AScreenWithoutAStack::WHATS_NEW_SHOWS);

        if (is_string($shows)) {
            $this->showStack($shows);
        }
    }

    /** What's new as this frame draws it, reading the first stack shown that has not been read. */
    public function news(): WhatIsNewAsShown
    {
        if ($this->shown instanceof WhatIsNewAsShown) {
            return $this->shown;
        }

        $stacks = $this->stacks->configured();
        $reads = new HowWhatIsNewReads($this->noticing);
        $listed = $this->listed ?? WhatEachStackListed::nothingYet();

        foreach ($reads->stacksShown($stacks, $this->stackShown) as $stack) {
            if (! $listed->hasAsked($stack->id()) && $this->mayReadItsStack()) {
                $listed = $this->read($stack, $listed);
            }
        }

        $this->listed = $listed;

        return $this->shown = $reads->shown($stacks, $listed, $this->kind, $this->stackShown, $this->clock->now());
    }

    /** Show one kind, or every kind. */
    public function showKind(string $kind): void
    {
        $this->kind = KindOfNews::tryFrom($kind) instanceof KindOfNews ? $kind : HowWhatIsNewReads::EVERYTHING;
    }

    /** Show one stack, or every stack. */
    public function showStack(string $stack): void
    {
        $this->stackShown = $this->stacks->configured()->knows(StackId::rememberedAs($stack))
            ? $stack
            : HowWhatIsNewReads::EVERYTHING;
    }

    /**
     * Everything shown is seen: every kind shown, on every stack shown.
     *
     * Only what is shown, so a filter narrows what this clears as well as what
     * is drawn.
     */
    public function markAllSeen(): void
    {
        $listed = $this->listed ?? WhatEachStackListed::nothingYet();
        $reads = new HowWhatIsNewReads($this->noticing);

        foreach ($reads->stacksShown($this->stacks->configured(), $this->stackShown) as $stack) {
            $news = $listed->of($stack->id());

            foreach (HowWhatIsNewReads::kindsShown($this->kind) as $kind) {
                if ($news instanceof TheNewsAsItems && $news->wasRead($kind)) {
                    $this->noticing->sawThemAll($stack->id(), $news->of($kind));
                }
            }
        }
    }

    /**
     * Mark one item seen, and every older one of its kind with it, and open where it belongs.
     *
     * An update opens Updates, a request Requests, and a problem Health, which
     * draws what is wrong without being asked.
     */
    public function open(string $stack, string $kind, string $named): void
    {
        $which = KindOfNews::tryFrom($kind);
        $id = StackId::rememberedAs($stack);
        $news = ($this->listed ?? WhatEachStackListed::nothingYet())->of($id);

        if (! $which instanceof KindOfNews || !$news instanceof TheNewsAsItems || ! $this->stacks->configured()->knows($id)) {
            return;
        }

        $said = $news->find($which, $named);

        if (!$said instanceof WhatAnItemSays) {
            return;
        }

        $on = $this->stacks->configured()->stack($id);
        $this->noticing->sawIt($on->id(), $said->item, $news->of($which));
        $this->goTo($said->item, $on);
    }

    /** Let go of what each stack listed, so the next frame reads them again. */
    public function again(): void
    {
        $this->listed = null;
    }

    public function render(): View
    {
        $this->aFrameBegins();
        $this->shown = null;

        return view('operator::what-is-new-on-every-stack');
    }

    /** Read one stack, holding what it listed or that it could not be reached. */
    private function read(Stack $stack, WhatEachStackListed $listed): WhatEachStackListed
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatEachStackListed => $this->reading->newsOn($stack, $session)->either(
                found: static fn(TheNewsOfAStack $news): WhatEachStackListed => $listed->listing($stack->id(), $news),
                met: function (Obstacle $why) use ($stack, $listed): WhatEachStackListed {
                    $this->letGoOfTheSession($why, $stack);

                    return $listed->unreached($stack->id(), $this->standings->lastKnownOf($stack->id()));
                },
            ),
            notHeld: fn(): WhatEachStackListed => $listed->unreached($stack->id(), $this->standings->lastKnownOf($stack->id())),
        );
    }

    /** Open the screen an item belongs to; a problem's screen draws what is wrong without being asked. */
    private function goTo(AnItem $item, Stack $stack): void
    {
        $goes = match ($item->kind()) {
            KindOfNews::Update => AStacksScreen::Updates,
            KindOfNews::Request => AStacksScreen::Requests,
            KindOfNews::Problem => AStacksScreen::Health,
        };

        $this->navigate($goes->forTheStack($stack->id()));
    }
}
