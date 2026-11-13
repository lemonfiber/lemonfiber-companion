<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Presenters\HowAFoundTitleReads;
use Modules\Household\Internal\Presenters\HowWhatCanBeAskedForReads;
use Modules\Household\Internal\ThePicturesItDraws;
use Modules\Household\Internal\ViewModels\WhatCanBeSearchedAsShown;
use Modules\Household\Internal\ViewModels\WhatTheSearchShows;
use Modules\Kernel\Api\AFoundTitle;
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
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Kernel\Api\WhichTitles;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use Override;

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
 * approval is the core's own. What can be searched among is the core's list
 * too: a chip is drawn for each kind and unit it lists, and none other.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class LookingForATitle extends NativeComponent
{
    use WritesItsTaps;
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::looking-for-a-title';

    /** What the member has typed, bound to the field. */
    public string $typed = '';

    /** What the search has shown so far. */
    public ?WhatTheSearchShows $shown = null;

    /** What the member chose to search among, by the word its chip hands back; the first offered until they choose. */
    public string $among = '';

    /** What the member can search among, read once on the tab's first frame. */
    public ?WhatCanBeAskedFor $askable = null;

    public function __construct(
        private readonly FindingTitles $finding,
        private readonly ThePicturesItDraws $pictures,
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

    /** What the member can search among, as the chips that offer it. */
    public function choices(): WhatCanBeSearchedAsShown
    {
        return new HowWhatCanBeAskedForReads()->chips($this->askable(), $this->which());
    }

    /** Search among this from now on, and search the words last searched for again at once, from the first page, where there were any. */
    public function choose(string $word): void
    {
        $this->among = $word;
        $searched = $this->shown?->found?->words;

        if ($searched instanceof WhatToLookFor) {
            $this->searchFor($searched->among($this->which()));
        }
    }

    /** Search for what is typed, among what was chosen, from its first page. */
    public function search(): void
    {
        $words = WhatToLookFor::typed($this->typed)->among($this->which());

        if ($words->isEnough()) {
            $this->searchFor($words);
        }
    }

    /** Read the page after the last one shown, where there is one. */
    public function more(): void
    {
        $found = $this->shown?->found;

        if ($found?->next === null) {
            return;
        }

        $next = WhereTheNextPageBegins::at($found->next);
        $this->shown = $this->asked($found->words, fn(Stack $stack, Session $session): HowASearchWent => $this->finding->pageAfter($stack, $session, $found->words, $next))->after($found);
    }

    /** Where signing in again is. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    /** Back on view, as after the lock let go of every picture: draw each title's poster again. */
    #[Override]
    public function onResume(): void
    {
        if (! $this->shown instanceof WhatTheSearchShows) {
            return;
        }

        $stack = $this->stack();
        $shown = $this->shown;

        $this->shown = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheSearchShows => $this->pictured($shown, $stack, $session),
            notHeld: static fn(): WhatTheSearchShows => $shown,
        );
    }

    /**
     * What the member can search among, asked of the core once and kept.
     *
     * Films and series alone where it could not be asked or read: nothing is
     * said of why until the member searches, and the search meets the same
     * thing in the core's words.
     */
    private function askable(): WhatCanBeAskedFor
    {
        if ($this->askable instanceof WhatCanBeAskedFor) {
            return $this->askable;
        }

        $stack = $this->stack();

        return $this->askable = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatCanBeAskedFor => $this->finding->whatCanBeAskedFor($stack, $session)->either(
                listed: static fn(WhatCanBeAskedFor $askable): WhatCanBeAskedFor => $askable,
                refused: static fn(): WhatCanBeAskedFor => WhatCanBeAskedFor::filmsAndSeriesAlone(),
            ),
            notHeld: static fn(): WhatCanBeAskedFor => WhatCanBeAskedFor::filmsAndSeriesAlone(),
        );
    }

    /** What the search looks among now: what the member chose, where it is still offered. */
    private function which(): WhichTitles
    {
        return $this->askable()->named($this->among);
    }

    /** Search for these words from their first page. */
    private function searchFor(WhatToLookFor $words): void
    {
        $this->shown = $this->asked($words, fn(Stack $stack, Session $session): HowASearchWent => $this->finding->firstPage($stack, $session, $words));
    }

    /** What the search shows, each title drawn with its poster. */
    private function pictured(WhatTheSearchShows $shown, Stack $stack, Session $session): WhatTheSearchShows
    {
        return $shown->pictured(fn(AFoundTitle $title): ?string => $this->pictures->ofAFoundTitle($stack, $session, $title));
    }

    /**
     * Resume the session, ask the core on it, and read what came back.
     *
     * @param Closure(Stack, Session): HowASearchWent $asking
     */
    private function asked(WhatToLookFor $words, Closure $asking): WhatTheSearchShows
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheSearchShows => $asking($stack, $session)->either(
                found: fn(WhatASearchFound $page): WhatTheSearchShows => $this->pictured(new HowAFoundTitleReads()->found($words, $page), $stack, $session),
                refused: function (Obstacle $why) use ($stack): WhatTheSearchShows {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAFoundTitleReads()->searchMet($why);
                },
            ),
            notHeld: static fn(): WhatTheSearchShows => WhatTheSearchShows::theSessionEnded(),
        );
    }
}
