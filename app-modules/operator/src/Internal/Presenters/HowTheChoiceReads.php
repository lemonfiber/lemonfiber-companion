<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheChoiceAsShown;
use Modules\Operator\Internal\ViewModels\TheChoiceTurnedOutToBe;

/**
 * What choosing a filler produces, as the fields a screen draws.
 *
 * Data in, view model out. Every name is the stack's; every refusal the stack
 * names for a choice is said in this app's words for it, chosen by its case.
 */
final readonly class HowTheChoiceReads
{
    /** No session is held for the stack, so nothing was asked. */
    public function signedOut(): TheChoiceTurnedOutToBe
    {
        return new TheChoiceTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), reading: null, said: '', saidWith: [], meaning: '', refused: null);
    }

    /** The stack worked the choice out, and nothing is written until the operator agrees. */
    public function read(AFill $fill): TheChoiceTurnedOutToBe
    {
        return new TheChoiceTurnedOutToBe(went: HowTheReadingWent::itCameBack(), reading: $this->reading($fill), said: '', saidWith: [], meaning: '', refused: null);
    }

    /** The stack made the choice. */
    public function made(AFill $fill): TheChoiceTurnedOutToBe
    {
        return new TheChoiceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            reading: null,
            said: 'stacks.wiring.fills.made',
            saidWith: ['service' => $fill->now()->named(), 'capability' => $fill->capability()->named()],
            meaning: '',
            refused: null,
        );
    }

    /**
     * The stack turned the choice down for a reason it names.
     *
     * A reading still shown stays in front of the operator: the same one where
     * only the reason could not be kept, and the one worked out again where
     * the reading agreed to had moved.
     */
    public function turnedDown(AFillTurnedDown $down, ?AFill $stillShown = null): TheChoiceTurnedOutToBe
    {
        $why = $down->why();

        return new TheChoiceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            reading: $stillShown instanceof AFill ? $this->reading($stillShown) : null,
            said: $this->saying($why),
            saidWith: [],
            // Already filling it is said whole by this app's words; every
            // other refusal keeps the stack's account of it beside them.
            meaning: $why === WhyTheFillWasTurnedDown::AlreadyFills ? '' : $down->said()->meaning(),
            refused: null,
        );
    }

    /** The stack refused for a reason of its own, in its words. */
    public function refused(ARefusalInItsWords $why): TheChoiceTurnedOutToBe
    {
        return new TheChoiceTurnedOutToBe(went: HowTheReadingWent::itCameBack(), reading: null, said: '', saidWith: [], meaning: '', refused: new HowARefusalReads()->inItsWords($why));
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheChoiceTurnedOutToBe
    {
        return new TheChoiceTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), reading: null, said: '', saidWith: [], meaning: '', refused: null);
    }

    /** The words for one refusal of a choice. */
    private function saying(WhyTheFillWasTurnedDown $why): string
    {
        return match ($why) {
            WhyTheFillWasTurnedDown::NoSuchService => 'stacks.wiring.fills.no_such_service',
            WhyTheFillWasTurnedDown::CannotFill => 'stacks.wiring.fills.cannot_fill',
            WhyTheFillWasTurnedDown::AlreadyFills => 'stacks.wiring.fills.already',
            WhyTheFillWasTurnedDown::NothingAsks => 'stacks.wiring.fills.nothing_asks',
            WhyTheFillWasTurnedDown::NowhereToKeepIt => 'stacks.wiring.fills.nowhere_to_keep',
            WhyTheFillWasTurnedDown::Moved => 'stacks.wiring.fills.moved',
            WhyTheFillWasTurnedDown::ReasonCannotBeKept => 'stacks.wiring.fills.reason_cannot_be_kept',
        };
    }

    /** The reading, as the lines drawn before the yes. */
    private function reading(AFill $fill): TheChoiceAsShown
    {
        $leaves = [];

        foreach ($fill->leaves() as $left) {
            $leaves[] = ['by' => $left->asking()->named(), 'capability' => $left->capability()->named()];
        }

        return new TheChoiceAsShown(
            capability: $fill->capability()->named(),
            now: $fill->now()->named(),
            reachesSaid: $fill->was()->isEmpty() ? 'stacks.wiring.fills.reaches_nothing' : 'stacks.wiring.fills.reaches_now',
            reachesWith: $fill->was()->isEmpty() ? [] : ['service' => HowTheLinksRead::named($fill->was())],
            askedBy: HowTheLinksRead::named($fill->askedBy()),
            leaves: $leaves,
        );
    }
}
