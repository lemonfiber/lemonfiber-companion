<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What this screen found running that the stack never put there.
 *
 * Empty is the ordinary answer and the screen says so in words: a machine
 * running only what its stack declares is the expected shape, and a blank list
 * would read as *nobody looked*. The question is answered by naming what is there;
 * saying clearly that nothing is, is the same answer.
 *
 * @see \Modules\Operator\Internal\Presenters\HowSomethingElseReads
 */
final readonly class WhatElseTurnedOutToBe
{
    /**
     * @param list<WhatOneOtherContainerSays>  $running    everything the stack did not declare, in the machine's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $running,
    ) {}
}
