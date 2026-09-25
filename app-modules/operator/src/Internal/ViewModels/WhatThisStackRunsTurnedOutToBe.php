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
 * ended is the sign-in screen; an obstacle is its own. Folding the first two
 * together would have a signed-out phone report a house where nothing is
 * running, which is the collapse {@see \Modules\Kernel\Api\WhatIsRunning}
 * refuses one layer up and this one must not rebuild.
 */
final readonly class WhatThisStackRunsTurnedOutToBe
{
    /**
     * @param list<WhatOneServiceSays> $services   everything it runs, in the stack's order
     * @param list<string>             $forms      the forms it has, whether or not anything in them runs
     * @param string                   $overall    the key for what it all amounts to, or empty where there is none
     * @param bool                     $isSettling whether anything here becomes something else by itself
     * @param list<string>                 $active  the forms running, as the stack counts them
     * @param list<AServiceLeftOutAsShown> $leftOut the services those forms left out, each with why
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $services,
        public array $forms,
        public string $overall,
        public bool $isSettling,
        public ?Disturbances $disturbs,
        public array $active,
        public array $leftOut,
    ) {}
}
