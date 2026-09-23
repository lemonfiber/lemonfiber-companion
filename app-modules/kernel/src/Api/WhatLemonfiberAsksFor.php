<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * One of the requests lemonfiber makes on its own account.
 *
 * Seven, and the closed set is the stack's claim: an eighth is a decision
 * somebody makes by adding it and answering what it sends, where it goes, what
 * switches it off and what that costs — not one that happens because somebody
 * built a request. Read as a closed set here for {@see HowFarItGoesBack}'s
 * reason: a request this app has no word for is refused at the reading rather
 * than drawn under the nearest name, and the nearest name to an unknown
 * connection is a claim about where somebody's data goes.
 */
enum WhatLemonfiberAsksFor: string
{
    /** Fetching the service images the stack runs. */
    case Registry = 'registry';

    /** Probing the source the community quality guides are synced from. */
    case Guides = 'guides';

    /** Asking what public address this machine's traffic comes out of. */
    case Echo = 'echo';

    /** Proving an indexer key against the indexer. */
    case Indexer = 'indexer';

    /** Proving a Usenet login against the provider. */
    case Usenet = 'usenet';

    /** Telling a household member the one thing the request service cannot carry. */
    case Household = 'household';

    /** Asking which version of lemonfiber itself has been released. */
    case Updates = 'updates';

    /** The catalogue key for this request's name, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.outbound.asks_for.%s', $this->value);
    }
}
