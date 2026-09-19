<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Why a report did not reach the operator's hands.
 *
 * Two cases, told apart for an obstacle's reason: one is something the operator
 * can do something about and the other is not, and one sentence for both is the
 * sentence that is unhelpful for whichever they are in.
 *
 * A closed set rather than a message, which is `D4`: a string could carry a
 * platform's own words, and a platform's words about a temporary file are not
 * words an operator can act on.
 */
enum WhyNothingWasShared: string
{
    /**
     * There was no report to hand over.
     *
     * The report was not assembled, so nothing was put in front of anybody. The
     * remedy is to ask for it again rather than to try the sheet again, which
     * is why this is not merged with the case below: that one says the report
     * exists and the platform would not show it.
     */
    case NothingToHandOver = 'nothing_to_hand_over';

    /**
     * The platform would not show a share sheet.
     *
     * Nothing the operator can fix, and the remedy is the other road: the
     * report's text is on the screen already, and copying it by hand is worse
     * but is not nothing.
     */
    case TheDeviceWouldNotOffer = 'the_device_would_not_offer';

    /** The key for what happened, which the screen shows. */
    public function saidOnTheScreen(): string
    {
        return sprintf('device.%s', $this->value);
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return sprintf('device.%s_action', $this->value);
    }
}
