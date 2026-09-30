<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the allowance screen draws: the core's sentences about what the
 * household may ask for, in its own order and wording, or what stopped it.
 */
final readonly class TheAllowanceTurnedOutToBe
{
    /** @param list<string> $sentences */
    public function __construct(
        public HowTheReadingWent $went,
        public array $sentences,
    ) {}
}
