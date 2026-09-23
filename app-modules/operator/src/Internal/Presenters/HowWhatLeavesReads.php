<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\OneOfOurRequests;
use Modules\Operator\Internal\ViewModels\OneOfTheirRequests;
use Modules\Operator\Internal\ViewModels\WhatLeavesTurnedOutToBe;

/**
 * What asking a stack what leaves it produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out.
 */
final readonly class HowWhatLeavesReads
{
    /** A service reaching somewhere, said with where. */
    public const string REACHES = 'stacks.outbound.theirs.reaches';

    /** A service recorded to reach nothing, which is an answer. */
    public const string REACHES_NOTHING = 'stacks.outbound.theirs.reaches_nothing';

    /** A service the stack has no record of, which is not an answer. */
    public const string UNRECORDED = 'stacks.outbound.theirs.unrecorded';

    /** This device no longer holds a session for that stack. */
    public function signedOut(): WhatLeavesTurnedOutToBe
    {
        return new WhatLeavesTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), ours: [], theirs: []);
    }

    /** The stack answered, and this is what leaves it. */
    public function this(WhatLeavesThisMachine $leaving): WhatLeavesTurnedOutToBe
    {
        $ours = [];

        foreach ($leaving->ours() as $request) {
            $ours[] = $this->ours($request);
        }

        $theirs = [];

        foreach ($leaving->theirs() as $request) {
            $theirs[] = $this->theirs($request);
        }

        return new WhatLeavesTurnedOutToBe(went: HowTheReadingWent::itCameBack(), ours: $ours, theirs: $theirs);
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): WhatLeavesTurnedOutToBe
    {
        return new WhatLeavesTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), ours: [], theirs: []);
    }

    /** One of lemonfiber's requests, as the row that draws it. */
    private function ours(ARequestOfOurs $request): OneOfOurRequests
    {
        return new OneOfOurRequests(
            asksForSaid: $request->asksFor()->saidOnTheScreen(),
            destinations: $this->destinations($request),
            purpose: $request->purpose(),
            sends: $request->sends(),
            allowedSaid: $request->allowed()->saidOnTheScreen(),
            switch: $request->switch(),
            cost: $request->cost(),
        );
    }

    /**
     * Where one request goes, as the list a template walks.
     *
     * Collected by hand rather than through `iterator_to_array`, whose
     * `preserve_keys` could not be wrong over a collection that holds a list
     * and so is an argument no test could defend (`C10`).
     *
     * @return list<string>
     */
    private function destinations(ARequestOfOurs $request): array
    {
        $found = [];

        foreach ($request->destinations() as $destination) {
            $found[] = $destination;
        }

        return $found;
    }

    /** One service's row, on the sentence its arm calls for. */
    private function theirs(ARequestOfTheirs $request): OneOfTheirRequests
    {
        $service = $request->service()->named();

        return $request->reaches(
            recorded: static fn(string $destination, string $purpose): OneOfTheirRequests => new OneOfTheirRequests(
                service: $service,
                reachesSaid: $destination === '' ? self::REACHES_NOTHING : self::REACHES,
                destination: $destination,
                purpose: $purpose,
            ),
            unrecorded: static fn(): OneOfTheirRequests => new OneOfTheirRequests(
                service: $service,
                reachesSaid: self::UNRECORDED,
                destination: '',
                purpose: '',
            ),
        );
    }
}
