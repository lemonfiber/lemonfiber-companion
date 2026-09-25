<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Turning an address into a code another device can scan off this one's screen.
 *
 * It takes an {@see AnAddressToHand} and nothing else, so the only thing it can
 * draw is an address as the stack sent it: a code is a second way of handing
 * that text over, never a way of handing over something this app wrote.
 *
 * Answers {@see AScannableCode::none()} rather than raising where the text
 * cannot be drawn, for the reason every port here answers rather than throws.
 */
interface Encoding
{
    /** The address, as squares to draw. */
    public function codeFor(AnAddressToHand $address): AScannableCode;
}
