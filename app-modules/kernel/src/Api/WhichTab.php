<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The four tabs a stack's screens are under, as what the phone keeps names them.
 *
 * The operator's bar draws them; this is the word each is noted down as, so a
 * build that redraws the bar still reads a tab noted by the one before.
 */
enum WhichTab: string
{
    case Health = 'health';
    case Services = 'services';
    case Updates = 'updates';
    case Repairs = 'repairs';
}
