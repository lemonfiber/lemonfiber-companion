<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A member's search for titles the house does not hold yet, answered by the core under their limits.
 *
 * The app asks the core and nothing else: never the request service, never a
 * catalogue. The core searches as the member the session belongs to and
 * leaves out a title their limits keep from them, so nothing here filters or
 * names whose search it is; an operator's session, which is nobody's, is
 * refused by the core in its own words.
 */
interface FindingTitles
{
    /** What the member the session belongs to can search for and ask for, as the core lists it. */
    public function whatCanBeAskedFor(Stack $stack, Session $session): HowTheKindsWent;

    /** The first page of a search for these words, among what they are looked for among, as whoever the session belongs to. */
    public function firstPage(Stack $stack, Session $session, WhatToLookFor $words): HowASearchWent;

    /** The page a previous page said comes next. */
    public function pageAfter(Stack $stack, Session $session, WhatToLookFor $words, WhereTheNextPageBegins $next): HowASearchWent;
}
