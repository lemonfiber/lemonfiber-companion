<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Operator\Internal\ViewModels\ARootAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\SomethingBesideAsShown;
use Modules\Operator\Internal\ViewModels\SomethingKeptAsShown;
use Modules\Operator\Internal\ViewModels\TheCopiesAsFound;
use Modules\Operator\Internal\ViewModels\WhatIsKeptTurnedOutToBe;

/**
 * What asking a stack what it keeps produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Each list keeps the stack's order. Whether a
 * thing holds a secret becomes a catalogue key. The kernel carries no value
 * for this to pass on.
 */
final readonly class HowWhatIsKeptReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): WhatIsKeptTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack said what it keeps, and the copies went as they went. */
    public function this(WhatThisMachineKeeps $keeps, TheCopiesAsFound $copies): WhatIsKeptTurnedOutToBe
    {
        return new WhatIsKeptTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            roots: $this->roots($keeps->roots()),
            kept: $this->kept($keeps->kept()),
            beside: $this->beside($keeps->beside()),
            copies: $copies,
        );
    }

    /** It did not say, and this is what the operator met. */
    public function met(Obstacle $why): WhatIsKeptTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack listed its copies, which may be none. */
    public function copies(TheCopies $copies): TheCopiesAsFound
    {
        $names = [];

        foreach ($copies as $name) {
            $names[] = $name;
        }

        return new TheCopiesAsFound(HowTheReadingWent::itCameBack(), $names);
    }

    /** It could not list them, and this is what the operator met. */
    public function copiesMet(Obstacle $why): TheCopiesAsFound
    {
        return new TheCopiesAsFound(HowTheReadingWent::somethingStopped($why), []);
    }

    /**
     * An answer with nothing in it, for a reading that did not come back.
     *
     * The copies take the same outcome. They are asked only once the first
     * reading has answered, so whatever stopped that reading stopped this one.
     */
    private function nothingFrom(HowTheReadingWent $went): WhatIsKeptTurnedOutToBe
    {
        return new WhatIsKeptTurnedOutToBe(
            went: $went,
            roots: [],
            kept: [],
            beside: [],
            copies: new TheCopiesAsFound($went, []),
        );
    }

    /** @return list<ARootAsShown> */
    private function roots(TheRoots $roots): array
    {
        $shown = [];

        foreach ($roots as $root) {
            $shown[] = new ARootAsShown(where: $root->where(), what: $root->what());
        }

        return $shown;
    }

    /** @return list<SomethingKeptAsShown> */
    private function kept(WhatIsKept $kept): array
    {
        $shown = [];

        foreach ($kept as $one) {
            $shown[] = new SomethingKeptAsShown(
                what: $one->what(),
                where: $one->where(),
                why: $one->why(),
                secretSaid: $one->secret()->saidOnTheScreen(),
            );
        }

        return $shown;
    }

    /** @return list<SomethingBesideAsShown> */
    private function beside(WhatIsBeside $beside): array
    {
        $shown = [];

        foreach ($beside as $one) {
            $shown[] = new SomethingBesideAsShown(what: $one->what(), why: $one->why());
        }

        return $shown;
    }
}
