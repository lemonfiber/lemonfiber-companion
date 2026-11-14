<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

/**
 * What the road from a search to a found title's page carries, besides the id in its path.
 *
 * The search is the only place that knows these, and the core has no read of
 * one found title by its id, so the page is handed them rather than asking.
 */
enum WhatAFoundTitleIsOpenedWith: string
{
    /** Whether it is a film, a series, music or books, as the search named it. */
    case Kind = 'found-kind';

    /** What a title of music or books is within its kind, empty for a film or a series. */
    case Unit = 'found-unit';

    /** Its name. */
    case Title = 'found-title';

    /** Who made it, empty where the core named nobody. */
    case By = 'found-by';

    /** The year it came out, empty where the core knew none. */
    case Year = 'found-year';

    /** Where it stands in the house. */
    case Stands = 'found-stands';
}
