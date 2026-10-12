<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which way a condition the core alerted on went: it started, or it stopped having started.
 */
enum WhichWayItWent: string
{
    case Onset = 'onset';

    case Resolved = 'resolved';
}
