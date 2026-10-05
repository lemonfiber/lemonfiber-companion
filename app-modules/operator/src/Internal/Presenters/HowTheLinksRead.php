<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;
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

    /** How many claimants it takes for there to be a choice between them. */
    private const int A_CHOICE = 2;

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

    /** Services, named in the stack's order. */
    public static function named(Services $services): string
    {
        $named = [];

        foreach ($services as $service) {
            $named[] = $service->named();
        }

        return implode(self::BETWEEN, $named);
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
                capability: '',
                asks: '',
                asksWith: [],
                settledSaid: 'stacks.wiring.fills.by_name',
                settledWith: ['service' => $service->named()],
                isContested: false,
                why: $why,
                claimants: [],
                choices: [],
            ),
        );
    }

    /** A capability asked for, with how it settled and every claimant. */
    private function asked(string $by, Capability $capability, Services $services, WhatSettledIt $settled, TheClaimants $claimants): ALinkAsShown
    {
        $reached = self::named($services);
        $asks = ['by' => $by, 'capability' => $capability->named()];

        return $settled->whichever(
            outright: fn(): ALinkAsShown => $this->settled($asks, $services, 'stacks.wiring.fills.outright', ['service' => $reached], $claimants),
            each: fn(): ALinkAsShown => $this->settled($asks, $services, 'stacks.wiring.fills.each', ['services' => $reached], $claimants),
            contested: fn(Services $contesting): ALinkAsShown => $this->settled($asks, $services, 'stacks.wiring.fills.contested', [], $claimants, inTheContestsOrder: $contesting),
            chosen: fn(Services $over, WhoSettledIt $whose, WhyItWasChosen $why): ALinkAsShown => $this->settled(
                $asks,
                $services,
                $whose === WhoSettledIt::Operator ? 'stacks.wiring.fills.chosen_operator' : 'stacks.wiring.fills.chosen_stack',
                ['service' => $reached, 'over' => self::named($over)],
                $claimants,
                why: $why->saying(
                    stated: static fn(string $said): WhatTheCatalogueNames => new WhatTheCatalogueNames($said),
                    unstated: static fn(): WhatTheCatalogueNames => new WhatTheCatalogueNames(''),
                )->said,
            ),
            unfilled: fn(): ALinkAsShown => $this->settled($asks, $services, 'stacks.wiring.fills.unfilled', [], $claimants),
        );
    }

    /**
     * One asked link, as the row that draws it.
     *
     * @param array{by: string, capability: string} $asks        the service that asked and the capability, which fill the line that heads it
     * @param array<string, string>                 $settledWith
     */
    private function settled(array $asks, Services $answering, string $settledSaid, array $settledWith, TheClaimants $claimants, ?Services $inTheContestsOrder = null, string $why = ''): ALinkAsShown
    {
        $shown = $inTheContestsOrder instanceof Services ? $this->contesting($inTheContestsOrder, $claimants) : $this->claiming($claimants);

        return new ALinkAsShown(
            by: $asks['by'],
            capability: $asks['capability'],
            asks: 'stacks.wiring.fills.asks',
            asksWith: $asks,
            settledSaid: $settledSaid,
            settledWith: $settledWith,
            isContested: $inTheContestsOrder instanceof Services,
            why: $why,
            claimants: $shown,
            choices: $this->choosable($shown, $answering),
        );
    }

    /**
     * The claimants an operator may choose to answer a capability, in the order they are shown.
     *
     * None where fewer than two services claim it, since there is nothing to
     * choose between, and never a service that answers it already.
     *
     * @param  list<AClaimantAsShown> $claimants
     * @return list<string>
     */
    private function choosable(array $claimants, Services $answering): array
    {
        if (count($claimants) < self::A_CHOICE) {
            return [];
        }

        $choices = [];

        foreach ($claimants as $claimant) {
            if (! $this->answers($claimant->name, $answering)) {
                $choices[] = $claimant->name;
            }
        }

        return $choices;
    }

    /** Whether a service is one of those answering. */
    private function answers(string $named, Services $answering): bool
    {
        foreach ($answering as $service) {
            if ($service->isTheSameAs(ServiceId::called($named))) {
                return true;
            }
        }

        return false;
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
}
