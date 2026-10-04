<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function array_filter;
use function array_values;

use Modules\Kernel\Api\Disturbances;
use Modules\Operator\Internal\Presenters\AgoAsShown;

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
 *
 * **A listing the phone kept is drawn whole, and waits.** It came back when it
 * was read, so it is drawn as a listing; what stopped the asking on this frame,
 * where something did, is a fact of its own beside it. Until a fresh listing
 * arrives, every control that would act on the stack is drawn and cannot be
 * used, with how long ago the listing was read beside it.
 */
final readonly class WhatThisStackRunsTurnedOutToBe
{
    /**
     * @param list<WhatOneServiceSays> $services   everything it runs, in the stack's order
     * @param string                   $overall    the key for what it all amounts to, or empty where there is none
     * @param bool                     $isSettling whether anything here becomes something else by itself
     * @param list<string>                 $active  the forms running, as the stack counts them
     * @param list<AServiceLeftOutAsShown> $leftOut the services those forms left out, each with why
     * @param HowTheReadingWent            $askedNow what this frame's asking met, which stands beside a kept listing where the stack did not answer
     * @param AgoAsShown                   $readAgo  how long ago the listing drawn was read, said only where it was kept
     * @param bool                         $waitsForTheStack whether the listing drawn is one the phone kept, so nothing on it can be acted on yet
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $services,
        public string $overall,
        public bool $isSettling,
        public ?Disturbances $disturbs,
        public array $active,
        public array $leftOut,
        public HowTheReadingWent $askedNow,
        public AgoAsShown $readAgo,
        public bool $waitsForTheStack,
    ) {}

    /**
     * What it runs, and what it would run and has not got: everything but
     * what nobody asked for.
     *
     * @return list<WhatOneServiceSays>
     */
    public function installed(): array
    {
        return array_values(array_filter($this->services, static fn(WhatOneServiceSays $service): bool => $service->isInstalled));
    }

    /**
     * What the stack could run and nothing asked for, in the stack's order.
     *
     * Folded away at the foot of the list rather than drawn among it: a row
     * for something nobody wanted, drawn like one that is missing, is a
     * warning about nothing.
     *
     * @return list<WhatOneServiceSays>
     */
    public function notInstalled(): array
    {
        return array_values(array_filter($this->services, static fn(WhatOneServiceSays $service): bool => ! $service->isInstalled));
    }
}
