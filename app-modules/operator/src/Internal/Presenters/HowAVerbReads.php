<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Operator\Internal\ViewModels\WhatAVerbTakesAwaySays;

/**
 * What a verb takes away, as the key and the number a line draws.
 *
 * A key and a number rather than a finished sentence, which is `L1`: a phrase
 * composed here is a phrase no translator can reach, and the two shapes read
 * differently in different languages.
 */
final readonly class HowAVerbReads
{
    public function of(WhatItTakesAway $takes): WhatAVerbTakesAwaySays
    {
        return $takes->either(
            bounded: static fn(int $seconds): WhatAVerbTakesAwaySays => new WhatAVerbTakesAwaySays(
                said: 'health.for_at_most',
                seconds: $seconds,
            ),
            // The awaiting's own line is the whole sentence rather than a word
            // dropped into one, so a language that puts *until* somewhere else
            // is not fighting a phrase this side assembled.
            openEnded: static fn(Awaiting $awaiting): WhatAVerbTakesAwaySays => new WhatAVerbTakesAwaySays(
                said: $awaiting->saidOnTheScreen(),
                // No number, rather than a nought. The line this names carries
                // no `:seconds`, so a number here would be one nothing reads —
                // and a nought that reached a line that did read it would say
                // *nought seconds*, which is the worst answer of all.
                seconds: null,
            ),
        );
    }
}
