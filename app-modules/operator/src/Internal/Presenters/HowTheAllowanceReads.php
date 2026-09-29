<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentences;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheAllowanceTurnedOutToBe;

/**
 * The household's allowance as the operator's screen draws it.
 *
 * The sentences are the core's and are shown as it wrote them: the
 * household's rules are the core's to state, and this app has no words of its
 * own for a policy it has never heard of.
 */
final readonly class HowTheAllowanceReads
{
    public function these(Sentences $said): TheAllowanceTurnedOutToBe
    {
        $lines = [];

        foreach ($said as $sentence) {
            $lines[] = $sentence->shown();
        }

        return new TheAllowanceTurnedOutToBe(went: HowTheReadingWent::itCameBack(), sentences: $lines);
    }

    public function met(Obstacle $why): TheAllowanceTurnedOutToBe
    {
        return new TheAllowanceTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), sentences: []);
    }

    public function signedOut(): TheAllowanceTurnedOutToBe
    {
        return new TheAllowanceTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), sentences: []);
    }
}
