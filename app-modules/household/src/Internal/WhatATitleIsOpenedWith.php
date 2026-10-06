<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

/**
 * What a title's screen is handed when a poster or the hero opens it: the
 * facts the shelf already answered about it.
 *
 * Handed over rather than read again, because the core answers no reading for
 * one title: what the screen can say about it is what the shelf said, and
 * asking the shelf again to find one title in it would be standing in for a
 * reading the core does not serve.
 */
enum WhatATitleIsOpenedWith: string
{
    /** Its name. */
    case Titled = 'titled';

    /** Its kind, as the core spells it. */
    case Medium = 'medium';

    /** The year it came out, or empty where the core could not date it. */
    case Year = 'year';
}
