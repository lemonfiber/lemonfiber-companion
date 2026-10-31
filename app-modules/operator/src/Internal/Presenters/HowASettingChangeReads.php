<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ChangingASetting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingMoves;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Operator\Internal\Listed;
use Modules\Operator\Internal\ViewModels\ASettingMoveAsShown;
use Modules\Operator\Internal\ViewModels\TheSettingChangeAsShown;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where changing settings has got to, as the template draws it.
 */
final readonly class HowASettingChangeReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    /** The stack is still at it. */
    public function running(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheSettingChangeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
            change: null,
        );
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheSettingChangeTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** What the run reported. */
    public function answered(TheSettingAsChanged $report): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: null,
            change: new TheSettingChangeAsShown(
                didSaid: $report->did()->saidOnTheScreen(),
                mayBeAgreed: $report->did() === WhatTheSettingRunDid::Rehearsed,
                toDefault: $report->change()->how() === ChangingASetting::Clear,
                moves: $this->moves($report->moves()),
                restarts: $this->listed($report->reaches()->restarts()),
                regenerates: $this->listed($report->reaches()->regenerates()),
                starts: $this->listed($report->reaches()->starts()),
                waiting: new HowAServiceWaitingReads()->these($report->reaches()->leavesWaiting()),
                unrestarted: $report->reaches()->unrestarted(),
            ),
        );
    }

    /**
     * Each setting the change moves, in the stack's order.
     *
     * @return list<ASettingMoveAsShown>
     */
    private function moves(TheSettingMoves $moves): array
    {
        $shown = [];

        foreach ($moves as $move) {
            $shown[] = new ASettingMoveAsShown(
                setting: $move->setting(),
                toDefault: ! $move->value()->isHeld(),
                value: $move->value()->said(),
                wasSet: $move->was()->isHeld(),
                was: $move->was()->said(),
                consequence: $move->consequence(),
            );
        }

        return $shown;
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

    private function without(WhereTheWorkStands $stands): TheSettingChangeTurnedOutToBe
    {
        return new TheSettingChangeTurnedOutToBe(stands: $stands, refused: null, change: null);
    }
}
