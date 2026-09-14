<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\WhatItTakesAway;

/**
 * How long a verb takes something away for, flattened to what a line draws.
 *
 * A key and a number rather than a finished sentence, which is `L1`: a phrase
 * composed here is a phrase no translator can reach, and the two shapes read
 * differently in different languages. The template hands both to `__()` and the
 * key decides whether the number is used.
 */
final readonly class WhatAVerbTakesAwaySays
{
    /**
     * @param string   $said    the catalogue key for the shape this arrived in
     * @param int|null $seconds how long for, where the shape has a length at all
     */
    private function __construct(
        public string $said,
        public ?int $seconds,
    ) {}

    public static function of(WhatItTakesAway $takes): self
    {
        return $takes->either(
            bounded: static fn(int $seconds): self => new self(
                said: 'health.for_at_most',
                seconds: $seconds,
            ),
            // The awaiting's own line is the whole sentence rather than a word
            // dropped into one, so a language that puts *until* somewhere else
            // is not fighting a phrase this side assembled.
            openEnded: static fn(Awaiting $awaiting): self => new self(
                said: $awaiting->saidOnTheScreen(),
                // No number, rather than a nought. The line this names carries
                // no `:seconds`, so a number here would be one nothing reads —
                // and a nought that reached a line that did read it would say
                // *nought seconds*, which is `N2-R14`'s worst answer.
                seconds: null,
            ),
        );
    }
}
