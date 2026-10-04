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
     * @param HowTheReadingWent    $went                 whether asking came back, and what stood in the way where it did not
     * @param bool                 $isWorking            whether the stack is still making it
     * @param bool                 $hasExpired           whether the code shown has stopped being good
     * @param bool                 $isCheckedDifferently whether the stack's check code is not the one a phone works out, so the code is not shown
     * @param string               $refused              the stack's refusal in its own words, or empty
     * @param ?APairingCodeAsShown $code                 the code to show, while it is good
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasExpired,
        public bool $isCheckedDifferently,
        public string $refused,
        public ?APairingCodeAsShown $code,
    ) {}
}
