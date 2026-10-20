<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Operator\Internal\Listed;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheSettingChangeAsShown;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;

/**
 * Where changing a setting has got to, as the template draws it.
 */
final readonly class HowASettingChangeReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** The stack is still at it. */
    public function running(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: new HowARefusalReads()->inItsWords($why),
            change: null,
        );
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheSettingChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** What the run reported. */
    public function answered(TheSettingAsChanged $report): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: null,
            change: new TheSettingChangeAsShown(
                didSaid: $report->did()->saidOnTheScreen(),
                mayBeAgreed: $report->did() === WhatTheSettingRunDid::Rehearsed,
                toDefault: ! $report->value()->isHeld(),
                value: $report->value()->said(),
                wasSet: $report->was()->isHeld(),
                was: $report->was()->said(),
                restarts: $this->listed($report->reaches()->restarts()),
                regenerates: $this->listed($report->reaches()->regenerates()),
                consequence: $report->reaches()->consequence(),
                unrestarted: $report->reaches()->unrestarted(),
            ),
        );
    }

    /** The services, named and listed in their order. */
    private function listed(Services $services): string
    {
        $named = [];

        foreach ($services as $service) {
            $named[] = $service->named();
        }

        return Listed::these(...$named);
    }

    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(went: $went, isWorking: $isWorking, hasEnded: $hasEnded, refused: null, change: null);
    }
}
