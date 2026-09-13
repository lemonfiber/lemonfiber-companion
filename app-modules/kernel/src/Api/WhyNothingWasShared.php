<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Why a report did not reach the operator's hands.
 *
 * Two cases, told apart for `N1-R10`'s reason: one is something the operator
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
     * There was nowhere to write the report before handing it over.
     *
     * A full disk, in practice — which on a phone holding this product's media
     * is the likeliest failure there is. The remedy is the operator's and it is
     * a real one, which is why this is not merged with the case below.
     */
    case NowhereToWriteIt = 'nowhere_to_write_it';

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
