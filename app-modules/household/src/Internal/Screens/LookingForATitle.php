<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Presenters\HowAFoundTitleReads;
use Modules\Household\Internal\ViewModels\WhatTheSearchShows;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\FindingTitles;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The member's Search tab: titles the house does not hold yet, found by the core under their limits.
 *
 * **It asks only when the member searches.** The words are sent when they
 * press search, never as they type, and fewer than the core searches for are
 * not sent at all.
 *
 * **One page at a time.** The next page is read as the member reaches the end
 * of the list, from the place the last page named, and added beneath it.
 *
 * **The core decides what is shown.** A title the member's limits keep from
 * them is not on any page, so nothing here filters, and the sentence on
 * approval is the core's own.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class LookingForATitle extends NativeComponent
{
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::looking-for-a-title';

    /** What the member has typed, bound to the field. */
    public string $typed = '';

    /** The words the rows on screen were found for, so a later page is of the same search. */
    public string $searchedFor = '';

    /** What the search has shown so far. */
    public ?WhatTheSearchShows $shown = null;

    public function __construct(
        private readonly FindingTitles $finding,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** What the search has shown so far, or that nothing was searched for yet. */
    public function answer(): WhatTheSearchShows
    {
        return $this->shown ?? WhatTheSearchShows::nothingYet();
    }

    /** Whether what is typed is enough to search for. */
    public function maySearch(): bool
    {
        return WhatToLookFor::typed($this->typed)->isEnough();
    }

    /** Search for what is typed, from its first page. */
    public function search(): void
    {
        $words = WhatToLookFor::typed($this->typed);

        if (! $words->isEnough()) {
            return;
        }

        $this->searchedFor = $words->said();
        $this->shown = $this->asked(fn(Stack $stack, Session $session): HowASearchWent => $this->finding->firstPage($stack, $session, $words));
    }

    /** Read the page after the last one shown, where there is one. */
    public function more(): void
    {
        $shown = $this->shown;

        if (! $shown instanceof WhatTheSearchShows || ! $shown->hasMore()) {
            return;
        }

        $words = WhatToLookFor::typed($this->searchedFor);
        $next = WhereTheNextPageBegins::at($shown->next);
        $this->shown = $shown->andThen($this->asked(fn(Stack $stack, Session $session): HowASearchWent => $this->finding->pageAfter($stack, $session, $words, $next)));
    }

    /** Where signing in again is. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    /**
     * Resume the session, ask the core on it, and read what came back.
     *
     * @param Closure(Stack, Session): HowASearchWent $asking
     */
    private function asked(Closure $asking): WhatTheSearchShows
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheSearchShows => $asking($stack, $session)->either(
                found: static fn(WhatASearchFound $page): WhatTheSearchShows => new HowAFoundTitleReads()->found($page),
                refused: function (Obstacle $why) use ($stack): WhatTheSearchShows {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAFoundTitleReads()->searchMet($why);
                },
            ),
            notHeld: static fn(): WhatTheSearchShows => WhatTheSearchShows::theSessionEnded(),
        );
    }
}
