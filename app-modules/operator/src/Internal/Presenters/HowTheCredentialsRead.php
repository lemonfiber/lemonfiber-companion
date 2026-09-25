<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Operator\Internal\ViewModels\ACredentialAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCredentialsTurnedOutToBe;

/**
 * What asking a stack which credentials it holds produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Each state keeps its own catalogue key, so
 * no two of them can be drawn as one warning.
 */
final readonly class HowTheCredentialsRead
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheCredentialsTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and these are what it holds. */
    public function these(TheCredentialsHeld $held): TheCredentialsTurnedOutToBe
    {
        $shown = [];

        foreach ($held as $credential) {
            $shown[] = $this->one($credential);
        }

        return new TheCredentialsTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            held: $shown,
            summary: $held->protection()->summary(),
            against: $this->listed($held->protection()->against()),
            notAgainst: $this->listed($held->protection()->notAgainst()),
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheCredentialsTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** One credential, as the row that draws it. */
    private function one(ACredentialHeld $credential): ACredentialAsShown
    {
        $consumers = [];

        foreach ($credential->consumers() as $consumer) {
            $consumers[] = $consumer;
        }

        return new ACredentialAsShown(
            name: $credential->name(),
            stateSaid: $credential->state()->saidOnTheScreen(),
            originSaid: $credential->origin()->saidOnTheScreen(),
            consumers: $consumers,
            advisory: $credential->advisory(),
        );
    }

    /**
     * One of the store's lists, as the list a template walks.
     *
     * Collected by hand rather than through `iterator_to_array`, for
     * {@see HowWhatLeavesReads}' reason (`C10`).
     *
     * @return list<string>
     */
    private function listed(Remarks $said): array
    {
        $found = [];

        foreach ($said as $one) {
            $found[] = $one;
        }

        return $found;
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheCredentialsTurnedOutToBe
    {
        return new TheCredentialsTurnedOutToBe(went: $went, held: [], summary: '', against: [], notAgainst: []);
    }
}
