<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\ALineAsShown;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\ViewModels\WhatYourAskSays;
use Modules\Household\Internal\ViewModels\WhatYourAskTurnedOutToBe;
use Modules\Household\Internal\WhereAnAskIsKept;
use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

use function trim;

/** One of a member's music or book asks, found by its reference among what the Requests tab reads, as its page draws it. */
final readonly class HowYourAskReads
{
    /** The ask `$reference` names among what they asked for, or that none is. */
    public function among(Requested $wanted, string $reference): WhatYourAskTurnedOutToBe
    {
        if (trim($reference) === '') {
            return WhatYourAskTurnedOutToBe::notAmongThem();
        }

        $named = RequestId::named($reference);

        foreach ($wanted as $one) {
            if ($one->id()->is($named)) {
                return WhatYourAskTurnedOutToBe::found($this->said($one));
            }
        }

        return WhatYourAskTurnedOutToBe::notAmongThem();
    }

    public function met(Obstacle $why): WhatYourAskTurnedOutToBe
    {
        return WhatYourAskTurnedOutToBe::somethingStopped($why);
    }

    public function signedOut(): WhatYourAskTurnedOutToBe
    {
        return WhatYourAskTurnedOutToBe::theSessionEnded();
    }

    private function said(Wanted $wanted): WhatYourAskSays
    {
        $kind = $wanted->called()->kind();
        $standing = $wanted->standing()->either(
            said: static fn(Waiting $said): InWords => new InWords($said->saidToTheMember()),
            unnamed: static fn(): InWords => new InWords(HowWhatAMemberAskedForReads::UNNAMED),
        )->said;
        $foundIn = $wanted->standing()->isReadyForYou() ? WhereAnAskIsKept::forThe($kind) : '';

        return $wanted->called()->either(
            named: static fn(string $title): WhatYourAskSays => new WhatYourAskSays(WhatOnePosterSays::ofARequest($title, $standing), $title, $kind->saidToTheMember(), $standing, $foundIn),
            notYet: static fn(KindOfRequest $notYet): WhatYourAskSays => new WhatYourAskSays(
                WhatOnePosterSays::ofARequestNotYetNamed(HowWhatAMemberAskedForReads::notYetNamed($notYet), $standing),
                new ALineAsShown(HowWhatAMemberAskedForReads::notYetNamed($notYet), []),
                $notYet->saidToTheMember(),
                $standing,
                $foundIn,
            ),
        );
    }
}
