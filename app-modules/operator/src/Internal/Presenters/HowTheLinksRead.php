<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function implode;

use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;
use Modules\Operator\Internal\ViewModels\AClaimantAsShown;
use Modules\Operator\Internal\ViewModels\ALinkAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheLinksTurnedOutToBe;
use Modules\Operator\Internal\WhatTheCatalogueNames;

/**
 * What asking a stack what it wires to what produces, as the fields a screen draws.
 *
 * Data in, view model out. Every name is the stack's, and every claimant
 * is drawn in the stack's order with where it came from, so a contest is a list
 * with nothing picked and a choice the stack made reads as the stack's.
 */
final readonly class HowTheLinksRead
{
    /** What stands between two services named on one line. */
    private const string BETWEEN = ', ';

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking.
     */
    public function signedOut(): TheLinksTurnedOutToBe
    {
        return new TheLinksTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), links: [], refused: null);
    }

    /** The stack answered, and this is what it wires to what. */
    public function these(TheLinks $links): TheLinksTurnedOutToBe
    {
        $shown = [];

        foreach ($links as $link) {
            $shown[] = $this->link($link);
        }

        return new TheLinksTurnedOutToBe(went: HowTheReadingWent::itCameBack(), links: $shown, refused: null);
    }

    /** The stack answered, and could not say what it wires to what, and this is why in its words. */
    public function refused(ARefusalInItsWords $why): TheLinksTurnedOutToBe
    {
        return new TheLinksTurnedOutToBe(went: HowTheReadingWent::itCameBack(), links: [], refused: new HowARefusalReads()->inItsWords($why));
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheLinksTurnedOutToBe
    {
        return new TheLinksTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), links: [], refused: null);
    }

    /** One link, on the arm it was reached by. */
    private function link(ALink $link): ALinkAsShown
    {
        $by = $link->by()->named();

        return $link->reaches()->whichever(
            asked: fn(Capability $capability, Services $services, WhatSettledIt $settled, TheClaimants $claimants): ALinkAsShown
                => $this->asked($by, $capability, $services, $settled, $claimants),
            byName: static fn(ServiceId $service, string $why): ALinkAsShown => new ALinkAsShown(
                by: $by,
                asks: '',
                asksWith: [],
                settledSaid: 'stacks.wiring.fills.by_name',
                settledWith: ['service' => $service->named()],
                isContested: false,
                why: $why,
                claimants: [],
            ),
        );
    }

    /** A capability asked for, with how it settled and every claimant. */
    private function asked(string $by, Capability $capability, Services $services, WhatSettledIt $settled, TheClaimants $claimants): ALinkAsShown
    {
        $reached = $this->named($services);

        return $settled->whichever(
            outright: fn(): ALinkAsShown => $this->settled($by, $capability, 'stacks.wiring.fills.outright', ['service' => $reached], $claimants),
            each: fn(): ALinkAsShown => $this->settled($by, $capability, 'stacks.wiring.fills.each', ['services' => $reached], $claimants),
            contested: fn(Services $contesting): ALinkAsShown => $this->settled($by, $capability, 'stacks.wiring.fills.contested', [], $claimants, inTheContestsOrder: $contesting),
            chosen: fn(Services $over, WhoSettledIt $whose, WhyItWasChosen $why): ALinkAsShown => $this->settled(
                $by,
                $capability,
                $whose === WhoSettledIt::Operator ? 'stacks.wiring.fills.chosen_operator' : 'stacks.wiring.fills.chosen_stack',
                ['service' => $reached, 'over' => $this->named($over)],
                $claimants,
                why: $why->saying(
                    stated: static fn(string $said): WhatTheCatalogueNames => new WhatTheCatalogueNames($said),
                    unstated: static fn(): WhatTheCatalogueNames => new WhatTheCatalogueNames(''),
                )->said,
            ),
            unfilled: fn(): ALinkAsShown => $this->settled($by, $capability, 'stacks.wiring.fills.unfilled', [], $claimants),
        );
    }

    /**
     * One asked link, as the row that draws it.
     *
     * @param array<string, string> $settledWith
     */
    private function settled(string $by, Capability $capability, string $settledSaid, array $settledWith, TheClaimants $claimants, ?Services $inTheContestsOrder = null, string $why = ''): ALinkAsShown
    {
        return new ALinkAsShown(
            by: $by,
            asks: 'stacks.wiring.fills.asks',
            asksWith: ['by' => $by, 'capability' => $capability->named()],
            settledSaid: $settledSaid,
            settledWith: $settledWith,
            isContested: $inTheContestsOrder instanceof Services,
            why: $why,
            claimants: $inTheContestsOrder instanceof Services ? $this->contesting($inTheContestsOrder, $claimants) : $this->claiming($claimants),
        );
    }

    /**
     * Every claimant with where it came from, in the order the stack sent them.
     *
     * @return list<AClaimantAsShown>
     */
    private function claiming(TheClaimants $claimants): array
    {
        $shown = [];

        foreach ($claimants as $claimant) {
            $shown[] = new AClaimantAsShown($claimant->service()->named(), new HowAnOriginReads()->of($claimant->from()));
        }

        return $shown;
    }

    /**
     * Every candidate in a contest, in the contest's own order, each with where it came from where the stack said.
     *
     * The contest's order rather than the origins', since that is the list a
     * choice is made from, and nothing here may reorder it.
     *
     * @return list<AClaimantAsShown>
     */
    private function contesting(Services $contesting, TheClaimants $claimants): array
    {
        $shown = [];

        foreach ($contesting as $candidate) {
            $from = null;

            foreach ($claimants as $claimant) {
                if ($claimant->service()->isTheSameAs($candidate)) {
                    $from = new HowAnOriginReads()->of($claimant->from());
                }
            }

            $shown[] = new AClaimantAsShown($candidate->named(), $from);
        }

        return $shown;
    }

    /** Services, named in the stack's order. */
    private function named(Services $services): string
    {
        $named = [];

        foreach ($services as $service) {
            $named[] = $service->named();
        }

        return implode(self::BETWEEN, $named);
    }
}
