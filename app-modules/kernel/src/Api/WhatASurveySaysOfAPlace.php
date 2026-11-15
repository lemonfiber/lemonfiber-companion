<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of its three answers the survey gave about where a service keeps something.
 */
enum WhatASurveySaysOfAPlace
{
    /** The host path it is mounted from. */
    case MountedFrom;

    /** The place could not be read. */
    case Unread;

    /** The service keeps nothing there. */
    case KeepsNone;
}
