<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What one download client said it was doing, before it was asked or after.
 *
 * `Unsaid` is its own case rather than an absence: a rehearsal asks nothing, so
 * nothing is said after it, and a client that could not be reached says
 * nothing at all. Neither is a client that stopped.
 */
enum WhatADownloadClientSaid: string
{
    /** It was fetching. */
    case Fetching = 'fetching';

    /** It was not fetching. */
    case Stopped = 'stopped';

    /** It said neither. */
    case Unsaid = 'unsaid';
}
