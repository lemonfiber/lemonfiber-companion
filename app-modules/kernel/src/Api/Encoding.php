<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Turning what the stack handed over into a code another device can scan off this one's screen.
 *
 * It takes an {@see AnAddressToHand}, an {@see APairingLine}, an
 * {@see AClientToHandOver}, an {@see AKeyClaim} or an
 * {@see ADistributorToInstall} and nothing else, so the only thing it can draw
 * is text as the stack sent it — an address to hand somebody, the line a phone
 * pairs with, or the code that points an app at the stack — or the one page
 * this app points to for what the phone lacks: a code is a second way of
 * handing that text over.
 *
 * Answers {@see AScannableCode::none()} rather than raising where the text
 * cannot be drawn, for the reason every port here answers rather than throws.
 */
interface Encoding
{
    /** What the stack handed over, as squares to draw. */
    public function codeFor(AnAddressToHand|APairingLine|AClientToHandOver|AKeyClaim|ADistributorToInstall $handed): AScannableCode;
}
