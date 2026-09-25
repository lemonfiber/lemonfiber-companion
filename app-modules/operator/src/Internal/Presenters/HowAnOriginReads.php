<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Operator\Internal\ViewModels\WhatWasThereBefore;
use Modules\Operator\Internal\ViewModels\WhereARowSaysItCameFrom;

/**
 * Who put something there, as the two things a template needs to say it.
 *
 * One fold for every screen that attributes — a setting, a check, a service —
 * because the arms are the same on all of them and only the sentence
 * differs, which is the template's to choose. Folded once here, every arm
 * produces a row: a presenter that filled in only the interesting arms would
 * leave the stack's own reading as something nobody established, and those
 * are the two the rules most want kept apart.
 */
final readonly class HowAnOriginReads
{
    public function of(WhoPutItThere $origin): WhereARowSaysItCameFrom
    {
        return $origin->whichever(
            bundled: static fn(): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Bundled),
            operator: static fn(): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Operator),
            plugin: static fn(string $named): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Plugin, $named),
            unknown: static fn(string $why): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Unknown, $why),
            overridden: fn(string $named, WhatItReplaced $replaced): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Overridden, $named, $this->before($replaced)),
            orphaned: static fn(string $named): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Orphaned, $named),
        );
    }

    /** What an override replaced, and where that came from. */
    private function before(WhatItReplaced $replaced): WhatWasThereBefore
    {
        $from = $this->of($replaced->from());

        return $replaced->whichever(
            held: static fn(string $value): WhatWasThereBefore => new WhatWasThereBefore('config.before.held', $value, $from),
            nothingSet: static fn(): WhatWasThereBefore => new WhatWasThereBefore('config.before.nothing_set', null, $from),
            withheld: static fn(): WhatWasThereBefore => new WhatWasThereBefore('config.before.withheld', null, $from),
        );
    }
}
