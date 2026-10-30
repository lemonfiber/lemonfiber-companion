<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where making a pairing code has got to, as the fields a screen draws.
 *
 * One arm at a time: working, a code to show, a code gone past its time, a
 * code whose check code this phone works out differently, the stack's
 * refusal, or what stood in the way. None of them is nothing asked yet,
 * which is the screen's opening.
 */
final readonly class HowThePairingCodeWent
{
    /**
     * @param WhereTheWorkStands    $stands                 where the work stands, and whether the stack could be asked at all
     * @param ?WhyThePairingCodeIsNotShown $notShown why the code the stack answered about is not shown, where it is not
     * @param ?string                      $refused  the stack's refusal in its own words, where it refused
     * @param ?APairingCodeAsShown         $code     the code to show, while it is good
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?WhyThePairingCodeIsNotShown $notShown = null,
        public ?string $refused = null,
        public ?APairingCodeAsShown $code = null,
    ) {}
}
