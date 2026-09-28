<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\ARemovalAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheRemovalTurnedOutToBe;

/**
 * Where taking somebody out has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never offers the yes beneath a removal already carried
 * out, and never draws an account left on the request service as done.
 */
final readonly class HowTheRemovalReads
{
    /** The screen was opened on nobody, so there is nothing to ask about. */
    public function namesNobody(): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), '', namesNobody: true);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(string $name): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded(), $name);
    }

    /** The stack is still working it out, or carrying it out after a yes. */
    public function running(string $name, bool $agreed): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, wasAgreed: $agreed, isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as refusing it or as it not having run. */
    public function ended(string $name, bool $agreed): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, wasAgreed: $agreed, hasEnded: true);
    }

    /** The stack refused, and this is its reason. */
    public function refused(string $because, string $name, bool $agreed): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, wasAgreed: $agreed, refusal: $because);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why, string $name, bool $agreed): TheRemovalTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why), $name, wasAgreed: $agreed);
    }

    /** What the stack answered: what taking them out would cost, or what it did. */
    public function answered(ARemoval $removal): TheRemovalTurnedOutToBe
    {
        $findings = [];

        foreach ($removal->findings() as $finding) {
            $findings[] = $finding;
        }

        return new TheRemovalTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            name: $removal->who()->name(),
            namesNobody: false,
            wasAgreed: $removal->wasCarriedOut(),
            isWorking: false,
            hasEnded: false,
            refusal: '',
            removal: new ARemovalAsShown(
                name: $removal->who()->name(),
                carriedOut: $removal->wasCarriedOut(),
                revokedSaid: $removal->revoked()->saidOnTheScreen(),
                isDone: $removal->wasCarriedOut() && $removal->revoked()->isDone(),
                requests: $removal->requests(),
                asksSaid: $removal->asksThroughTheRequestService() ? 'stacks.removal.asks' : 'stacks.removal.does_not_ask',
                findings: $findings,
            ),
        );
    }

    /** A state with no removal in it. */
    private function without(
        HowTheReadingWent $went,
        string $name,
        bool $namesNobody = false,
        bool $wasAgreed = false,
        bool $isWorking = false,
        bool $hasEnded = false,
        string $refusal = '',
    ): TheRemovalTurnedOutToBe {
        return new TheRemovalTurnedOutToBe(
            went: $went,
            name: $name,
            namesNobody: $namesNobody,
            wasAgreed: $wasAgreed,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refusal: $refusal,
            removal: null,
        );
    }
}
