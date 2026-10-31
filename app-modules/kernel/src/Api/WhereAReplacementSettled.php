<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What a replacement came to, as the stack tells the outcomes apart.
 *
 * Only {@see self::Replaced} is a replacement that landed; every other case is
 * said in its own words, and none of them is drawn as one.
 */
enum WhereAReplacementSettled: string
{
    /** Nothing was attempted: this only said what a replacement would do. */
    case Rehearsed = 'rehearsed';

    /** Proven, and now the one in force. */
    case Replaced = 'replaced';

    /** Made, and not proven to answer. */
    case ReplacedUnproven = 'replaced-unproven';

    /** Not proven, so nothing was changed. */
    case Unproven = 'unproven';

    /** Refused by what holds it, so nothing was changed. */
    case Refused = 'refused';

    /** Nothing in the stack holds it by that name. */
    case Unknown = 'unknown';

    /** A replacement for it does not come from here. */
    case Elsewhere = 'elsewhere';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.credentials.replace.settled.%s', $this->value);
    }
}
