<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What this screen found running that the stack never put there.
 *
 * Empty is the ordinary answer and the screen says so in words: a machine
 * running only what its stack declares is the expected shape, and a blank list
 * would read as *nobody looked*. `N2-R21` is answered by naming what is there;
 * saying clearly that nothing is, is the same answer.
 *
 * @see \Modules\Operator\Internal\Presenters\HowSomethingElseReads
 */
final readonly class WhatElseTurnedOutToBe
{
    /**
     * @param bool                             $isSignedIn whether this device still holds a session for the stack
     * @param string                           $met        the key for what stood in the way, or empty where nothing did
     * @param string                           $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneOtherContainerSays>  $running    everything the stack did not declare, in the machine's order
     */
    public function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $running,
    ) {}
}
