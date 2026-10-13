<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What making a key from this phone came to, as the screen says it.
 *
 * The link where a key was made; otherwise the stack's refusal in its words, what stood in the
 * way, or the phone's own sentence for what happened on it.
 */
final readonly class WhatMintingShows
{
    /** @param string $said the catalogue key for what happened on the phone, or empty */
    public function __construct(
        public ?AKeyClaimAsShown $claim,
        public ?ARefusalAsShown $refused,
        public ?HowTheReadingWent $met,
        public string $said,
    ) {}
}
