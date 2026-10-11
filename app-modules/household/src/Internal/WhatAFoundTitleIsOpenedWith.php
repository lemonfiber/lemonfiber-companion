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
    /** Whether it is a film or a series, as the search named it. */
    case Kind = 'found-kind';

    /** Its name. */
    case Title = 'found-title';

    /** The year it came out, empty where the core knew none. */
    case Year = 'found-year';

    /** Where it stands in the house. */
    case Stands = 'found-stands';
}
