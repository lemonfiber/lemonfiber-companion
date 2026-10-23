<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking the indexers about one item, which spends a search of their allowance, and following the search.
 *
 * Sent with the term and season the trace was read with, never rehearsed: a
 * rehearsed search would still spend the search. It answers with the trace,
 * read again once the search came back.
 */
interface AskingTheIndexers
{
    /**
     * Search for this item.
     *
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function search(Stack $stack, Session $session, WhatToFollow $following): WhatBecameOfTheWork;

    /**
     * What became of it, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
