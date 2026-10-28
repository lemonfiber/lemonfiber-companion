<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Zone;
use Modules\Operator\Internal\ViewModels\APairingCodeAsShown;
use Modules\Operator\Internal\ViewModels\HowThePairingCodeWent;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where making a pairing code has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never draws a code that has stopped being good.
 */
final readonly class HowThePairingCodeReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** The stack is still making it. */
    public function working(): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::running());
    }

    /** The stack refused, and this is its reason. */
    public function refused(string $because): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::answered(), refused: $because);
    }

    /** The code shown has stopped being good, so it is not shown any more. */
    public function expired(): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::answered(), hasExpired: true);
    }

    /** The stack's check code is not the one a phone typing the line works out, so the code is not shown. */
    public function checkedDifferently(): HowThePairingCodeWent
    {
        return $this->without(WhereTheWorkStands::answered(), isCheckedDifferently: true);
    }

    /**
     * The code the stack made, with its line drawn as a code and its moment
     * on the clock of the phone showing it.
     *
     * The squares are passed in rather than made here, because making them is
     * a port's work and a presenter asks nothing of anybody.
     */
    public function made(APairingCode $code, AScannableCode $drawn, Zone $here): HowThePairingCodeWent
    {
        return new HowThePairingCodeWent(
            stands: WhereTheWorkStands::answered(),
            hasExpired: false,
            isCheckedDifferently: false,
            refused: '',
            code: new APairingCodeAsShown(
                squares: new HowACodeReads()->squares($drawn),
                line: $code->line()->carried(),
                compare: $code->compare(),
                until: $here->timeOfDayAt($code->expiresAt())->shown(),
                address: $code->address(),
                caution: $code->caution(),
                replacing: $code->replacing(),
            ),
        );
    }

    /** A state with no code in it. */
    private function without(
        WhereTheWorkStands $stands,
        bool $hasExpired = false,
        bool $isCheckedDifferently = false,
        string $refused = '',
    ): HowThePairingCodeWent {
        return new HowThePairingCodeWent(
            stands: $stands,
            hasExpired: $hasExpired,
            isCheckedDifferently: $isCheckedDifferently,
            refused: $refused,
            code: null,
        );
    }
}
