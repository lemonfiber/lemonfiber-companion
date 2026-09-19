<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What kind of thing one holding is.
 *
 * The core's own list and the whole of it, which is what makes an enum right
 * here rather than {@see ServiceId}'s value object: the media a household keeps
 * is a closed set this product decides, not a set each machine declares. A
 * fourth kind arriving from a stack is a stack this build does not understand,
 * and it says so by name rather than by silently rendering as something else.
 *
 * No label. What a screen calls a film is text a person reads, so it comes from
 * the translator against a key — a name written here would be English on a
 * Dutch phone.
 */
enum Medium: string
{
    /** One thing, watched in one sitting. */
    case Film = 'film';

    /** Episodes, which a shelf counts as one row rather than as many. */
    case Series = 'series';

    /**
     * Something the core holds and this list has no better word for.
     *
     * Carried rather than dropped. A holding a member can see on their shelf
     * and cannot see here would be this app deciding what is worth showing
     * them, which is the core's decision and not a rendering one.
     */
    case Other = 'other';

    /**
     * The key a screen shows this under.
     *
     * Derived from the case rather than listed, so a medium added to the wire
     * cannot be given a key here and forgotten in the catalogue — the derived
     * key is checked against what the translator holds.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('household.medium.%s', $this->value);
    }
}
