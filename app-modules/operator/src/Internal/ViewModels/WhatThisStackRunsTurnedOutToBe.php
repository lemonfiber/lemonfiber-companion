<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Disturbances;

/**
 * What asking a stack what it is running produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowAListingReads} folds the
 * answer once and the template reads fields, because Blade has no `either()`
 * and cannot be given one.
 *
 * **Three states, and a stack running nothing is one of them.** Everything off
 * is the answer an operator opens this screen to change; a session that has
 * ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the first two
 * together would have a signed-out phone report a house where nothing is
 * running, which is the collapse {@see \Modules\Kernel\Api\WhatIsRunning}
 * refuses one layer up and this one must not rebuild.
 */
final readonly class WhatThisStackRunsTurnedOutToBe
{
    /**
     * @param bool                     $isSignedIn whether this device still holds a session for the stack
     * @param string                   $met        the key for what stood in the way, or empty where nothing did
     * @param string                   $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneServiceSays> $services   everything it runs, in the stack's order
     * @param list<string>             $forms      the forms it has, whether or not anything in them runs
     * @param string                   $overall    the key for what it all amounts to, or empty where there is none
     * @param bool                     $isSettling whether anything here becomes something else by itself
     */
    public function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $services,
        public array $forms,
        public string $overall,
        public bool $isSettling,
        public ?Disturbances $disturbs,
    ) {}
}
